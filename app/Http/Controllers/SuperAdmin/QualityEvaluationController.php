<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Country;
use App\Models\QualityEvaluation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QualityEvaluationController extends Controller
{
    /**
     * Eager-loaded relationships used by both the index page and the export.
     */
    private const WITH_RELATIONS = [
        'user:id,first_name,last_name,username',
        'branch:id,name,name_ar,country_id,brand_id',
        'branch.country:id,name,name2',
        'branch.brand:id,name,name2',
        'emailLogs:id,quality_evaluation_id,company_id,to_emails,cc_emails,sent_at',
    ];

    /**
     * Apply the shared filter set used by the index page and the export.
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

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
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
     * Append localized_name on the eager-loaded branch / country / brand for
     * a single evaluation (used by both the page payload and the export).
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
     * Display a paginated, filterable list of every QualityEvaluation in the
     * system for Super Admin review.
     */
    public function index(Request $request): Response
    {
        $query = QualityEvaluation::query()
            ->with(self::WITH_RELATIONS)
            ->withCount('followUps');
        $this->applyFilters($query, $request);

        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(5, min($perPage, 100));

        $evaluations = $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $evaluations->getCollection()->each(fn (QualityEvaluation $e) => $this->appendLocalizedNames($e));

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

        return Inertia::render('SuperAdmin/QualityEvaluations/Index', [
            'evaluations' => $evaluations,
            'countries' => $countries,
            'brands' => $brands,
            'branches' => $branches,
            'filters' => $request->only([
                'country_id', 'brand_id', 'branch_id',
                'status', 'type', 'start_date', 'end_date',
            ]),
            'locale' => app()->getLocale(),
        ]);
    }

    /**
     * Stream the filtered evaluation list as a UTF-8 CSV file (opens cleanly in
     * Excel; the BOM ensures Arabic characters render correctly).
     */
    public function export(Request $request): StreamedResponse
    {
        $query = QualityEvaluation::query()->with(self::WITH_RELATIONS);
        $this->applyFilters($query, $request);

        $filename = 'quality-evaluations-' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ];

        $columns = [
            'ID', 'Title', 'Type', 'Status',
            'Country', 'Brand', 'Branch',
            'Evaluator', 'Total Score', 'Warning Flag', 'Created At',
        ];

        return response()->stream(function () use ($query, $columns) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel detects the encoding for Arabic text.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $columns);

            $query->orderByDesc('created_at')->chunk(500, function ($evaluations) use ($handle) {
                foreach ($evaluations as $evaluation) {
                    $this->appendLocalizedNames($evaluation);

                    $user = $evaluation->user;
                    $evaluator = '';
                    if ($user) {
                        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                        $evaluator = $fullName !== '' ? $fullName : ($user->username ?? '');
                    }

                    fputcsv($handle, [
                        $evaluation->id,
                        $evaluation->title,
                        $evaluation->type,
                        $evaluation->status,
                        optional(optional($evaluation->branch)->country)->localized_name,
                        optional(optional($evaluation->branch)->brand)->localized_name,
                        optional($evaluation->branch)->localized_name,
                        $evaluator,
                        $evaluation->total_score,
                        $evaluation->warning_flag ? 'Yes' : 'No',
                        optional($evaluation->created_at)->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
