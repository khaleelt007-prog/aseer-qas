<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Country;
use App\Models\QualityEvaluation;
use App\Services\QualityEvaluationFollowUpService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FollowUpController extends Controller
{
    /**
     * Eager-loaded relationships used by the index page.
     */
    private const WITH_RELATIONS = [
        'branch:id,name,name_ar,country_id,brand_id',
        'branch.country:id,name,name2',
        'branch.brand:id,name,name2',
        'followUps',
    ];

    public function __construct(
        private readonly QualityEvaluationFollowUpService $followUpService,
    ) {
    }

    /**
     * Apply the Country / Brand / Branch + date filters used across the
     * Super Admin panel. Mirrors `SuperAdmin\QualityEvaluationController`.
     */
    private function applyFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('country_id')) {
            $countryBranchIds = Branch::where('country_id', $request->input('country_id'))->pluck('id');
            $query->whereIn('branch_id', $countryBranchIds);
        }

        if ($request->filled('brand_id')) {
            $brandBranchIds = Branch::where('brand_id', $request->input('brand_id'))->pluck('id');
            $query->whereIn('branch_id', $brandBranchIds);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        return $query;
    }

    /**
     * List every QualityEvaluation that has at least one QcAnswerFollowUp
     * record so Super Admins can audit follow-up activity across the system.
     */
    public function index(Request $request): Response
    {
        $query = QualityEvaluation::query()
            ->with(self::WITH_RELATIONS)
            ->whereHas('followUps');

        $this->applyFilters($query, $request);
        $this->followUpService->appendFollowUpCounts($query);

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(5, min($perPage, 100));

        $evaluations = $query
            ->warningFirst()
            ->paginate($perPage)
            ->withQueryString();

        $evaluations->getCollection()->each(function (QualityEvaluation $evaluation) {
            if ($evaluation->branch) {
                $evaluation->branch->append('localized_name');
                $evaluation->branch->country?->append('localized_name');
                $evaluation->branch->brand?->append('localized_name');
            }
        });

        $branches = Branch::where('is_catering', 0)->get()->map(fn (Branch $branch) => [
            'id' => $branch->id,
            'name' => $branch->name,
            'name_ar' => $branch->name_ar,
            'localized_name' => $branch->localized_name,
            'country_id' => $branch->country_id,
            'brand_id' => $branch->brand_id,
        ]);

        $countryIds = $branches->pluck('country_id')->unique()->filter()->values()->toArray();
        $countries = Country::whereIn('id', $countryIds)->get()->map(fn (Country $country) => [
            'id' => $country->id,
            'name' => $country->name,
            'name_ar' => $country->name2,
            'localized_name' => $country->localized_name,
        ]);

        $brandIds = $branches->pluck('brand_id')->unique()->filter()->values()->toArray();
        $brands = Brand::whereIn('id', $brandIds)->get()->map(fn (Brand $brand) => [
            'id' => $brand->id,
            'name' => $brand->name,
            'name_ar' => $brand->name2,
            'localized_name' => $brand->localized_name,
        ]);

        return Inertia::render('SuperAdmin/FollowUps/Index', [
            'evaluations' => $evaluations,
            'countries' => $countries,
            'brands' => $brands,
            'branches' => $branches,
            'filters' => $request->only([
                'country_id', 'brand_id', 'branch_id', 'start_date', 'end_date',
            ]),
            'locale' => app()->getLocale(),
        ]);
    }

    /**
     * Render a read-only follow-up detail view for a single evaluation.
     *
     * Reuses the section-grouped payload built by
     * `QualityEvaluationFollowUpService::buildShowPayload()` so the display
     * stays consistent with the regular follow-up screen.
     */
    public function show(int $id): Response
    {
        $evaluation = QualityEvaluation::query()
            ->whereHas('followUps')
            ->findOrFail($id);

        return Inertia::render('SuperAdmin/FollowUps/Show', [
            ...$this->followUpService->buildShowPayload($evaluation),
            'locale' => app()->getLocale(),
        ]);
    }
}
