<?php

namespace App\Http\Controllers;

use App\Models\QcAnswer;
use App\Models\QualityEvaluation;
use App\Services\DataAccessService;
use App\Services\QualityEvaluationFollowUpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class QualityEvaluationFollowUpController extends Controller
{
    public function __construct(
        private readonly DataAccessService $dataAccessService,
        private readonly QualityEvaluationFollowUpService $followUpService,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['country_id', 'brand_id', 'branch_id', 'start_date', 'end_date']);
        $query = $this->followUpService->getEligibleEvaluationsQuery(Auth::user());

        $this->followUpService->applyFilters($query, $filters);
        $this->followUpService->appendFollowUpCounts($query);

        $evaluations = $query
            ->with('branch')
            ->warningFirst()
            ->paginate(10)
            ->withQueryString();

        $evaluations->getCollection()->each(fn (QualityEvaluation $evaluation) => $evaluation->branch?->append('localized_name'));

        return Inertia::render('QualityEvaluationFollowUp/Index', [
            'evaluations' => $evaluations,
            ...$this->followUpService->getFilterData(),
            'filters' => $filters,
            'locale' => app()->getLocale(),
        ]);
    }

    public function show(QualityEvaluation $qualityEvaluation): Response
    {
        $this->authorizeEvaluation($qualityEvaluation);

        if (!$this->followUpService->isEligibleEvaluation($qualityEvaluation->load('answers.question'))) {
            abort(404);
        }

        return Inertia::render('QualityEvaluationFollowUp/Show', [
            ...$this->followUpService->buildShowPayload($qualityEvaluation),
            'locale' => app()->getLocale(),
        ]);
    }

    public function setDeadline(Request $request, QualityEvaluation $qualityEvaluation, QcAnswer $answer): RedirectResponse
    {
        $validated = $request->validate([
            'expected_deadline' => ['required', 'date'],
        ]);

        $this->authorizeAnswer($qualityEvaluation, $answer);
        $this->followUpService->setDeadline($answer, $validated['expected_deadline'], $request->user());

        return back()->with('success', 'Follow-up deadline saved successfully.');
    }

    public function storeComment(Request $request, QualityEvaluation $qualityEvaluation, QcAnswer $answer): RedirectResponse
    {
        $validated = $request->validate([
            'comment_type' => ['required', 'in:branch_reply,qc_comment'],
            'comment_text' => ['required', 'string', 'max:5000'],
        ]);

        $this->authorizeAnswer($qualityEvaluation, $answer);
        $this->followUpService->addComment($answer, $validated['comment_type'], $validated['comment_text'], $request->user());

        return back()->with('success', 'Follow-up comment added successfully.');
    }

    public function markSolved(Request $request, QualityEvaluation $qualityEvaluation, QcAnswer $answer): RedirectResponse
    {
        $this->authorizeAnswer($qualityEvaluation, $answer);
        $this->followUpService->markSolved($answer, $request->user());

        return back()->with('success', 'Issue marked as solved successfully.');
    }

    public function markSkipped(Request $request, QualityEvaluation $qualityEvaluation, QcAnswer $answer): RedirectResponse
    {
        $this->authorizeAnswer($qualityEvaluation, $answer);
        $this->followUpService->markSkipped($answer, $request->user());

        return back()->with('success', 'Issue skipped successfully.');
    }

    private function authorizeEvaluation(QualityEvaluation $qualityEvaluation): void
    {
        if ($qualityEvaluation->user_id !== Auth::id() && (int) auth()->user()->group_id !== 1) {
            abort(403);
        }

        if (!$this->dataAccessService->hasAccessToBranchFromSession($qualityEvaluation->branch_id)) {
            abort(403, 'You do not have access to this evaluation.');
        }
    }

    private function authorizeAnswer(QualityEvaluation $qualityEvaluation, QcAnswer $answer): void
    {
        $this->authorizeEvaluation($qualityEvaluation);
        $qualityEvaluation->loadMissing('answers.question');

        if (!$this->followUpService->isEligibleEvaluation($qualityEvaluation)) {
            abort(404);
        }

        $answer->loadMissing('question');

        if ((int) $answer->quality_evaluation_id !== (int) $qualityEvaluation->id) {
            abort(404);
        }

        if (!$this->followUpService->isBadAnswer($answer)) {
            abort(422, 'Follow-up actions are only allowed for checklist answers scored 0 or 0.5.');
        }
    }
}