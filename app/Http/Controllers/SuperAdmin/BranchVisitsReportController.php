<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Country;
use App\Models\QualityEvaluation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BranchVisitsReportController extends Controller
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
     * filter set used by all summary queries and the drilldown.
     */
    private function applyFilters(Builder $query, Request $request): Builder
    {
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
     * Apply only the Country / Brand / Branch hierarchy filter to a Branch
     * query (used by the "Not Visited" tab and shared by helpers).
     */
    private function applyBranchScopeFilters(Builder $query, Request $request): Builder
    {
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->input('country_id'));
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        if ($request->filled('branch_id')) {
            $query->where('id', $request->input('branch_id'));
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
     * Build a normalized branch payload (with country / brand) for the page.
     */
    private function branchPayload(?Branch $branch): ?array
    {
        if (!$branch) {
            return null;
        }
        $branch->append('localized_name');
        if ($branch->country) {
            $branch->country->append('localized_name');
        }
        if ($branch->brand) {
            $branch->brand->append('localized_name');
        }

        return [
            'id' => (int) $branch->id,
            'name' => $branch->name,
            'name_ar' => $branch->name_ar,
            'localized_name' => $branch->localized_name,
            'country' => $branch->country ? [
                'id' => (int) $branch->country->id,
                'name' => $branch->country->name,
                'name_ar' => $branch->country->name2,
                'localized_name' => $branch->country->localized_name,
            ] : null,
            'brand' => $branch->brand ? [
                'id' => (int) $branch->brand->id,
                'name' => $branch->brand->name,
                'name_ar' => $branch->brand->name2,
                'localized_name' => $branch->brand->localized_name,
            ] : null,
        ];
    }

    /**
     * Build the standard Country / Brand / Branch dropdown payload used by
     * the QualityFilter component on the page.
     */
    private function filterOptions(): array
    {
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

        return [$countries, $brands, $branches];
    }

    /**
     * Render the Branch Visits report — three tabs: Top Branches, Not
     * Visited, and Needs Attention.
     */
    public function index(Request $request): Response
    {
        // ---------- Top Branches ----------
        $topQuery = QualityEvaluation::query()
            ->where('quality_evaluations.status', 'completed')
            ->whereNotNull('quality_evaluations.completed_at');
        $this->applyFilters($topQuery, $request);

        $topRows = $topQuery
            ->select(
                'quality_evaluations.branch_id',
                DB::raw('COUNT(*) AS total'),
                DB::raw('COUNT(DISTINCT DATE(quality_evaluations.completed_at)) AS unique_days'),
                DB::raw('MIN(DATE(quality_evaluations.completed_at)) AS first_date'),
                DB::raw('MAX(DATE(quality_evaluations.completed_at)) AS last_date')
            )
            ->groupBy('quality_evaluations.branch_id')
            ->orderByDesc('total')
            ->get();

        $topBranchesById = Branch::with(['country:id,name,name2', 'brand:id,name,name2'])
            ->whereIn('id', $topRows->pluck('branch_id')->all())
            ->get()
            ->keyBy('id');

        $topBranches = $topRows->map(function ($row) use ($topBranchesById) {
            $uniqueDays = (int) $row->unique_days;
            $averageWeeks = null;
            if ($uniqueDays > 1 && $row->first_date && $row->last_date) {
                $first = Carbon::parse($row->first_date);
                $last = Carbon::parse($row->last_date);
                $spanDays = abs($last->diffInDays($first));
                if ($spanDays > 0) {
                    $averageWeeks = round(($spanDays / 7) / ($uniqueDays - 1), 2);
                }
            }

            return [
                'branch_id' => (int) $row->branch_id,
                'branch' => $this->branchPayload($topBranchesById->get($row->branch_id)),
                'total' => (int) $row->total,
                'unique_days' => $uniqueDays,
                'first_date' => $row->first_date,
                'last_date' => $row->last_date,
                'average_weeks' => $averageWeeks,
            ];
        })->values();

        // ---------- Branches Not Visited ----------
        $visitedQuery = QualityEvaluation::query()->where('quality_evaluations.status', 'completed');
        $this->applyFilters($visitedQuery, $request);
        $visitedBranchIds = $visitedQuery
            ->select('quality_evaluations.branch_id')
            ->distinct()
            ->pluck('branch_id')
            ->all();

        $notVisitedQuery = Branch::with(['country:id,name,name2', 'brand:id,name,name2'])
            ->where('is_catering', 0);
        $this->applyBranchScopeFilters($notVisitedQuery, $request);
        if (!empty($visitedBranchIds)) {
            $notVisitedQuery->whereNotIn('id', $visitedBranchIds);
        }

        $notVisited = $notVisitedQuery->orderBy('name')->get()
            ->map(fn (Branch $b) => $this->branchPayload($b))
            ->values();

        // ---------- Branches Needing Attention ----------
        $threshold = (int) $request->input('days_threshold', 30);
        $threshold = max(1, min($threshold, 365));
        $cutoff = now()->subDays($threshold)->toDateString();

        $attentionQuery = QualityEvaluation::query()
            ->where('quality_evaluations.status', 'completed')
            ->whereNotNull('quality_evaluations.completed_at');

        if ($request->filled('country_id')) {
            $countryBranchIds = Branch::where('country_id', $request->input('country_id'))->pluck('id');
            $attentionQuery->whereIn('quality_evaluations.branch_id', $countryBranchIds);
        }
        if ($request->filled('brand_id')) {
            $brandBranchIds = Branch::where('brand_id', $request->input('brand_id'))->pluck('id');
            $attentionQuery->whereIn('quality_evaluations.branch_id', $brandBranchIds);
        }
        if ($request->filled('branch_id')) {
            $attentionQuery->where('quality_evaluations.branch_id', $request->input('branch_id'));
        }

        $attentionRows = $attentionQuery
            ->select(
                'quality_evaluations.branch_id',
                DB::raw('MAX(quality_evaluations.completed_at) AS last_completed_at')
            )
            ->groupBy('quality_evaluations.branch_id')
            ->havingRaw('MAX(quality_evaluations.completed_at) < ?', [$cutoff])
            ->orderBy('last_completed_at', 'asc')
            ->get();

        $attentionBranchesById = Branch::with(['country:id,name,name2', 'brand:id,name,name2'])
            ->whereIn('id', $attentionRows->pluck('branch_id')->all())
            ->get()
            ->keyBy('id');

        $needsAttention = $attentionRows->map(function ($row) use ($attentionBranchesById) {
            $last = $row->last_completed_at ? Carbon::parse($row->last_completed_at) : null;
            return [
                'branch_id' => (int) $row->branch_id,
                'branch' => $this->branchPayload($attentionBranchesById->get($row->branch_id)),
                'last_completed_at' => $row->last_completed_at,
                'days_since' => $last ? (int) floor(abs($last->diffInDays(now()))) : null,
                'last_visit_human' => $last ? $last->diffForHumans(['parts' => 1]) : null,
            ];
        })->values();

        [$countries, $brands, $branches] = $this->filterOptions();

        return Inertia::render('SuperAdmin/Reports/BranchVisits', [
            'topBranches' => $topBranches,
            'notVisited' => $notVisited,
            'needsAttention' => $needsAttention,
            'countries' => $countries,
            'brands' => $brands,
            'branches' => $branches,
            'filters' => $request->only([
                'country_id', 'brand_id', 'branch_id',
                'start_date', 'end_date', 'days_threshold',
            ]),
            'days_threshold' => $threshold,
            'locale' => app()->getLocale(),
        ]);
    }

    /**
     * Return the paginated list of completed evaluations for a single branch
     * (the drilldown popup on the Top Branches tab). Honors the same
     * Country / Brand / Branch + date range filters as the summary.
     */
    public function evaluations(Request $request, int $branchId): JsonResponse
    {
        $query = QualityEvaluation::query()
            ->with(self::WITH_RELATIONS)
            ->where('quality_evaluations.branch_id', $branchId)
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
