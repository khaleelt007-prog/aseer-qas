<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Country;
use App\Models\QualityEvaluation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TopEvaluatorReportController extends Controller
{
    /**
     * Eager-loaded relationships used by the drilldown evaluations endpoint.
     */
    private const WITH_RELATIONS = [
        'user:id,first_name,last_name,username',
        'branch:id,name,name_ar,country_id,brand_id',
        'branch.country:id,name,name2',
        'branch.brand:id,name,name2',
    ];

    /**
     * Apply the shared Country / Brand / Branch + completed_at date range
     * filter set used by both the summary and the drilldown queries.
     */
    private function applyFilters(Builder $query, Request $request): Builder
    {
        // Column names are qualified with the table name because the summary
        // query joins sma_users, which also exposes a `branch_id` column.
        if ($request->filled('country_id')) {
            $countryBranchIds = Branch::where('country_id', $request->input('country_id'))->pluck('id');
            $query->whereIn('quality_evaluations.branch_id', $countryBranchIds);
        }

        if ($request->filled('brand_id')) {
            $brandBranchIds = Branch::where('brand_id', $request->input('brand_id'))->pluck('id');
            $query->whereIn('quality_evaluations.branch_id', $brandBranchIds);
        }

        if ($request->filled('branch_id')) {
            $query->where('quality_evaluations.branch_id', $request->input('branch_id'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('quality_evaluations.completed_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('quality_evaluations.completed_at', '<=', $request->input('end_date'));
        }

        return $query;
    }

    /**
     * Append localized_name on the eager-loaded branch / country / brand.
     */
    private function appendLocalizedNames(QualityEvaluation $evaluation): void
    {
        if ($evaluation->branch) {
            $evaluation->branch->append('localized_name');
            if ($evaluation->branch->country) {
                $evaluation->branch->country->append('localized_name');
            }
            if ($evaluation->branch->brand) {
                $evaluation->branch->brand->append('localized_name');
            }
        }
    }

    /**
     * Render the Top Evaluators report — a ranked list of users by the
     * number of completed evaluations they performed in the filtered window.
     */
    public function index(Request $request): Response
    {
        $query = QualityEvaluation::query()->where('quality_evaluations.status', 'completed');
        $this->applyFilters($query, $request);

        $evaluators = $query
            ->join('sma_users', 'sma_users.id', '=', 'quality_evaluations.user_id')
            ->select(
                'quality_evaluations.user_id',
                'sma_users.username',
                DB::raw("CONCAT(sma_users.first_name, ' ', sma_users.last_name) AS full_name"),
                DB::raw('COUNT(*) AS total')
            )
            ->groupBy(
                'quality_evaluations.user_id',
                'sma_users.username',
                'sma_users.first_name',
                'sma_users.last_name'
            )
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'user_id' => (int) $row->user_id,
                'username' => $row->username,
                'full_name' => trim((string) $row->full_name),
                'total' => (int) $row->total,
            ]);

        // Filter dropdown data — Super Admin sees everything.
        $branches = Branch::where('is_catering', 0)->get()->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'name_ar' => $branch->name_ar,
                'localized_name' => $branch->localized_name,
                'country_id' => $branch->country_id,
                'brand_id' => $branch->brand_id,
            ];
        });

        $countryIds = $branches->pluck('country_id')->unique()->filter()->values()->toArray();
        $countries = Country::whereIn('id', $countryIds)->get()->map(function ($country) {
            return [
                'id' => $country->id,
                'name' => $country->name,
                'name_ar' => $country->name2,
                'localized_name' => $country->localized_name,
            ];
        });

        $brandIds = $branches->pluck('brand_id')->unique()->filter()->values()->toArray();
        $brands = Brand::whereIn('id', $brandIds)->get()->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'name_ar' => $brand->name2,
                'localized_name' => $brand->localized_name,
            ];
        });

        return Inertia::render('SuperAdmin/Reports/TopEvaluators', [
            'evaluators' => $evaluators,
            'countries' => $countries,
            'brands' => $brands,
            'branches' => $branches,
            'filters' => $request->only([
                'country_id', 'brand_id', 'branch_id',
                'start_date', 'end_date',
            ]),
            'locale' => app()->getLocale(),
        ]);
    }

    /**
     * Return the paginated list of completed evaluations for a single user
     * (the drilldown popup on the Top Evaluators report). Honors the same
     * Country / Brand / Branch + date range filters as the summary.
     */
    public function evaluations(Request $request, int $user): JsonResponse
    {
        $query = QualityEvaluation::query()
            ->with(self::WITH_RELATIONS)
            ->where('quality_evaluations.user_id', $user)
            ->where('quality_evaluations.status', 'completed');

        $this->applyFilters($query, $request);

        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(5, min($perPage, 100));

        $evaluations = $query->orderByDesc('quality_evaluations.completed_at')
            ->paginate($perPage)
            ->withQueryString();

        $evaluations->getCollection()->each(fn (QualityEvaluation $e) => $this->appendLocalizedNames($e));

        return response()->json($evaluations);
    }
}
