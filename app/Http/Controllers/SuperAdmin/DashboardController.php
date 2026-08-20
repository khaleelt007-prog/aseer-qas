<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Country;
use App\Models\QcAnswerFollowUp;
use App\Models\QualityEvaluation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Days threshold used to flag a branch as "needing attention" — mirrors
     * the default in `SuperAdmin\BranchVisitsReportController`.
     */
    private const ATTENTION_THRESHOLD_DAYS = 30;

    /**
     * Filter keys consumed by the dashboard. Mirrors the global Super Admin
     * hierarchy + date filter set documented in §3 of the user guide.
     */
    private const FILTER_KEYS = [
        'country_id', 'brand_id', 'branch_id', 'start_date', 'end_date',
    ];

    /**
     * Apply the standard Super Admin Country / Brand / Branch + date range
     * filter to a `quality_evaluations` query. Date range is matched against
     * `created_at` to stay consistent with `SuperAdmin\QualityEvaluationController`.
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
     * Apply only the Country / Brand / Branch hierarchy filter to a `Branch`
     * (or `quality_evaluations` for the needs-attention query) builder. Used
     * where the date range is intentionally ignored — for example the 30-day
     * "needs attention" cutoff, which is anchored to `now()`.
     */
    private function applyHierarchyFilters(Builder $query, Request $request, string $branchColumn = 'branch_id'): Builder
    {
        if ($request->filled('country_id')) {
            $countryBranchIds = Branch::where('country_id', $request->input('country_id'))->pluck('id');
            $query->whereIn($branchColumn, $countryBranchIds);
        }

        if ($request->filled('brand_id')) {
            $brandBranchIds = Branch::where('brand_id', $request->input('brand_id'))->pluck('id');
            $query->whereIn($branchColumn, $brandBranchIds);
        }

        if ($request->filled('branch_id')) {
            $query->where($branchColumn, $request->input('branch_id'));
        }

        return $query;
    }

    /**
     * Build the Country / Brand / Branch dropdown payload used by the
     * `<QualityFilter>` component on the dashboard.
     */
    private function filterOptions(): array
    {
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

        return [$countries, $brands, $branches];
    }

    /**
     * Display the Super Admin dashboard with QualityEvaluation statistics.
     */
    public function index(Request $request): Response
    {
        $totalEvaluations = (int) $this->applyFilters(QualityEvaluation::query(), $request)->count();

        $statusCounts = $this->applyFilters(QualityEvaluation::query(), $request)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $completedCount = (int) ($statusCounts['completed'] ?? 0);
        $pendingCount = (int) ($statusCounts['pending'] ?? 0);

        $averageTotalScore = (float) $this->applyFilters(QualityEvaluation::query(), $request)
            ->avg('total_score');

        $typeCounts = $this->applyFilters(QualityEvaluation::query(), $request)
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $checklistCount = (int) ($typeCounts['checklist'] ?? 0);
        $regularCount = (int) ($typeCounts['regular'] ?? 0);

        $topEvaluators = $this->topEvaluators($request);
        $branchVisits = $this->branchVisitMetrics($request);
        $followUps = $this->followUpSummary($request);

        $recentWarnings = $this->applyFilters(QualityEvaluation::query(), $request)
            ->where('warning_flag', true)
            ->with(['user:id,first_name,last_name,username', 'branch:id,name'])
            ->orderByRaw('COALESCE(warning_flagged_at, completed_at, created_at) DESC')
            ->limit(10)
            ->get([
                'id',
                'user_id',
                'branch_id',
                'title',
                'total_score',
                'status',
                'type',
                'warning_flag',
                'warning_flagged_at',
                'completed_at',
                'created_at',
            ]);

        [$countries, $brands, $branches] = $this->filterOptions();

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => [
                'total_evaluations' => $totalEvaluations,
                'status' => [
                    'completed' => $completedCount,
                    'pending' => $pendingCount,
                    'other' => max(0, $totalEvaluations - $completedCount - $pendingCount),
                ],
                'average_total_score' => round($averageTotalScore, 2),
                'types' => [
                    'checklist' => $checklistCount,
                    'regular' => $regularCount,
                    'other' => max(0, $totalEvaluations - $checklistCount - $regularCount),
                ],
            ],
            'top_evaluators' => $topEvaluators,
            'branch_visits' => $branchVisits,
            'follow_ups' => $followUps,
            'attention_threshold_days' => self::ATTENTION_THRESHOLD_DAYS,
            'recent_warnings' => $recentWarnings,
            'countries' => $countries,
            'brands' => $brands,
            'branches' => $branches,
            'filters' => $request->only(self::FILTER_KEYS),
            'locale' => app()->getLocale(),
        ]);
    }

    /**
     * Top 5 evaluators by completed-evaluation count — mirrors the summary
     * query in `SuperAdmin\TopEvaluatorReportController::index()`. The
     * dashboard groups on the unjoined `quality_evaluations` table and loads
     * user names in a second query so `applyFilters()` can stay column-name
     * agnostic.
     */
    private function topEvaluators(Request $request): array
    {
        $base = $this->applyFilters(QualityEvaluation::query(), $request)
            ->where('status', 'completed');

        $rows = $base->select('user_id', DB::raw('COUNT(*) AS total'))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $users = User::query()
            ->whereIn('id', $rows->pluck('user_id')->all())
            ->get(['id', 'first_name', 'last_name', 'username'])
            ->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $user = $users->get($row->user_id);
            $fullName = $user ? trim(($user->first_name ?? '').' '.($user->last_name ?? '')) : '';

            return [
                'user_id' => (int) $row->user_id,
                'username' => $user->username ?? null,
                'full_name' => $fullName,
                'total' => (int) $row->total,
            ];
        })->values()->all();
    }

    /**
     * Branch visit coverage and the top of the "needs attention" list. The
     * coverage figure honors the active hierarchy + date range; the
     * needs-attention list uses only the hierarchy filter and the 30-day
     * cutoff (anchored to `now()`), matching `BranchVisitsReportController`.
     */
    private function branchVisitMetrics(Request $request): array
    {
        $branchScope = $this->applyHierarchyFilters(
            Branch::where('is_catering', 0),
            $request,
            'id'
        );
        $scopedBranchIds = $branchScope->pluck('id');
        $totalBranches = $scopedBranchIds->count();

        $visitedBranchesCount = (int) $this->applyFilters(QualityEvaluation::query(), $request)
            ->where('status', 'completed')
            ->whereIn('branch_id', $scopedBranchIds)
            ->distinct()
            ->count('branch_id');

        $coverage = $totalBranches > 0
            ? round(($visitedBranchesCount / $totalBranches) * 100, 1)
            : 0.0;

        $cutoff = now()->subDays(self::ATTENTION_THRESHOLD_DAYS)->toDateString();

        $attentionQuery = QualityEvaluation::query()
            ->where('status', 'completed')
            ->whereNotNull('completed_at');
        $this->applyHierarchyFilters($attentionQuery, $request);

        $attentionRows = $attentionQuery
            ->select(
                'branch_id',
                DB::raw('MAX(completed_at) AS last_completed_at')
            )
            ->groupBy('branch_id')
            ->havingRaw('MAX(completed_at) < ?', [$cutoff])
            ->orderBy('last_completed_at', 'asc')
            ->get();

        $topAttention = $attentionRows->take(5);

        $attentionBranchesById = Branch::with([
            'country:id,name,name2',
            'brand:id,name,name2',
        ])
            ->whereIn('id', $topAttention->pluck('branch_id')->all())
            ->get()
            ->keyBy('id');

        $needsAttentionTop = $topAttention->map(function ($row) use ($attentionBranchesById) {
            $branch = $attentionBranchesById->get($row->branch_id);
            if ($branch) {
                $branch->append('localized_name');
                $branch->country?->append('localized_name');
                $branch->brand?->append('localized_name');
            }
            $last = $row->last_completed_at ? Carbon::parse($row->last_completed_at) : null;

            return [
                'branch_id' => (int) $row->branch_id,
                'branch' => $branch ? [
                    'id' => (int) $branch->id,
                    'localized_name' => $branch->localized_name,
                    'country_name' => $branch->country?->localized_name,
                    'brand_name' => $branch->brand?->localized_name,
                ] : null,
                'last_completed_at' => $row->last_completed_at,
                'days_since' => $last ? (int) floor(abs($last->diffInDays(now()))) : null,
            ];
        })->values()->all();

        return [
            'total_branches' => $totalBranches,
            'visited_branches' => $visitedBranchesCount,
            'coverage_percentage' => $coverage,
            'needs_attention_count' => $attentionRows->count(),
            'needs_attention_top' => $needsAttentionTop,
        ];
    }

    /**
     * Aggregate counts for QcAnswerFollowUp records broken down by status,
     * including a derived `overdue` count (open follow-ups whose
     * `expected_deadline` is in the past). Scoped to the same evaluations as
     * the rest of the dashboard via `whereHas('qualityEvaluation', ...)`.
     */
    private function followUpSummary(Request $request): array
    {
        $applyEvalFilter = fn (Builder $q) => $this->applyFilters($q, $request);

        $base = QcAnswerFollowUp::query()
            ->whereHas('qualityEvaluation', $applyEvalFilter);

        $statusCounts = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $open = (int) ($statusCounts[QcAnswerFollowUp::STATUS_OPEN] ?? 0);
        $solved = (int) ($statusCounts[QcAnswerFollowUp::STATUS_SOLVED] ?? 0);
        $skipped = (int) ($statusCounts[QcAnswerFollowUp::STATUS_SKIPPED] ?? 0);

        $overdue = (int) (clone $base)
            ->where('status', QcAnswerFollowUp::STATUS_OPEN)
            ->whereNotNull('expected_deadline')
            ->whereDate('expected_deadline', '<', now()->toDateString())
            ->count();

        $evaluationsWithFollowUps = (int) $this->applyFilters(QualityEvaluation::query(), $request)
            ->has('followUps')
            ->count();

        return [
            'open' => $open,
            'solved' => $solved,
            'skipped' => $skipped,
            'total' => $open + $solved + $skipped,
            'overdue' => $overdue,
            'evaluations_with_follow_ups' => $evaluationsWithFollowUps,
        ];
    }
}

