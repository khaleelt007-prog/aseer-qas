<?php

namespace App\Http\Controllers;

use App\Jobs\SendQcReportEmail;
use App\Models\QualityEvaluation;
use App\Models\QualityEvaluationPhoto;
use App\Models\EvaluationItem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\Branch;
use App\Models\Country;
use App\Models\Brand;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\PermissionService;
use App\Services\DataAccessService;
use App\Services\QcTemplateService;

class QualityEvaluationController extends Controller
{
    protected $permissionService;
    protected $dataAccessService;
    protected $qcTemplateService;

    public function __construct(PermissionService $permissionService, DataAccessService $dataAccessService, QcTemplateService $qcTemplateService)
    {
        $this->permissionService = $permissionService;
        $this->dataAccessService = $dataAccessService;
        $this->qcTemplateService = $qcTemplateService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $isSuperAdmin = $user->group_id === 1;

        // Super admin sees all evaluations; regular users see only their own
        $query = $isSuperAdmin
            ? QualityEvaluation::query()
            : QualityEvaluation::where('user_id', $user->id);

        // Apply branch filtering based on user's data access
        $this->dataAccessService->applyBranchFilterFromSession($query);

        // Apply optional filters
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

        $evaluations = $query->with('branch')->orderBy('created_at', 'desc')->paginate(10);

        $evaluations->getCollection()->each(function ($evaluation) {
            if ($evaluation->branch) {
                $evaluation->branch->append('localized_name');
            }
        });

        // Get filtered branches based on user's data access (same logic as create)
        $branchQuery = Branch::where('is_catering', 0);
        $this->dataAccessService->applyBranchFilterFromSession($branchQuery, 'id');

        $branches = $branchQuery->get()->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'name_ar' => $branch->name_ar,
                'localized_name' => $branch->localized_name,
                'country_id' => $branch->country_id,
                'brand_id' => $branch->brand_id,
            ];
        });

        // Get unique country IDs from accessible branches
        $countryIds = $branches->pluck('country_id')->unique()->filter()->values()->toArray();
        $countries = Country::whereIn('id', $countryIds)->get()->map(function ($country) {
            return [
                'id' => $country->id,
                'name' => $country->name,
                'name_ar' => $country->name_ar,
                'localized_name' => $country->localized_name,
            ];
        });

        // Get unique brand IDs from accessible branches
        $brandIds = $branches->pluck('brand_id')->unique()->filter()->values()->toArray();
        $brands = Brand::whereIn('id', $brandIds)->get()->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'name_ar' => $brand->name_ar,
                'localized_name' => $brand->localized_name,
            ];
        });

        return Inertia::render('QualityEvaluation/Index', [
            'evaluations' => $evaluations,
            'countries' => $countries,
            'brands' => $brands,
            'branches' => $branches,
            'filters' => $request->only(['country_id', 'brand_id', 'branch_id', 'start_date', 'end_date']),
            'locale' => app()->getLocale(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $evaluationItems = EvaluationItem::orderBy('sort_order')->get()->map(function ($item) {
            return [
                'id' => str_replace(' ', '_', strtolower($item->name)),
                'title' => $item->name,
                'title_ar' => $item->name_ar,
                'localized_title' => $item->localized_name,
                'weight' => $item->weight,
                'exclude_from_score' => $item->exclude_from_score,
                'overwrite_total_score_if_zero' => $item->overwrite_total_score_if_zero,
            ];
        });

        // Get filtered branches based on user's data access
        $branchQuery = Branch::where('is_catering', 0);

        // Apply data access filtering
        $this->dataAccessService->applyBranchFilterFromSession($branchQuery, 'id');

        $branches = $branchQuery->get()->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'name_ar' => $branch->name_ar,
                'localized_name' => $branch->localized_name,
                'country_id' => $branch->country_id,
                'brand_id' => $branch->brand_id,
            ];
        });

        // Get unique country IDs from accessible branches
        $countryIds = $branches->pluck('country_id')->unique()->filter()->values()->toArray();
   
        // Get countries only for accessible branches
        $countries = Country::whereIn('id', $countryIds)->get()->map(function ($country) {
            return [
                'id' => $country->id,
                'name' => $country->name,
                'name_ar' => $country->name_ar,
                'localized_name' => $country->localized_name,
            ];
        });

        // Get unique brand IDs from accessible branches
        $brandIds = $branches->pluck('brand_id')->unique()->filter()->values()->toArray();

        // Get brands only for accessible branches
        $brands = Brand::whereIn('id', $brandIds)->get()->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'name_ar' => $brand->name_ar,
                'localized_name' => $brand->localized_name,
            ];
        });
        
        return Inertia::render('QualityEvaluation/Create', [
            'evaluationItems' => $evaluationItems,
            'branches' => $branches,
            'countries' => $countries,
            'brands' => $brands,
            'locale' => app()->getLocale(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Check if this is a checklist template submission
        $isChecklistTemplate = $request->has('is_checklist_template') && $request->boolean('is_checklist_template');

        // Get evaluation items for dynamic validation
        $evaluationItems = EvaluationItem::all();

        // Build dynamic validation rules
        $rules = [
            'branch_id' => 'required|exists:sma_branches,id',
            'title' => 'nullable|string|max:255',
            'comments' => 'nullable|string|max:2000',
            'status' => ['required', Rule::in(['draft', 'completed'])],
            'extra_points' => 'nullable|integer|min:-5|max:5',
            'photo_ids' => 'nullable|string', // JSON array of photo IDs
            'photo_sections' => 'nullable|string', // JSON array of section IDs for photos
        ];

        // Add dynamic validation rules based on form type
        if ($isChecklistTemplate) {
            // For checklist template, validate answers
            $rules['answers'] = 'nullable|array';
            $rules['answers.*'] = 'nullable|string|max:1000';
        } else {
            // For evaluation items, validate scores
            foreach ($evaluationItems as $item) {
                $identifier = $item->identifier;
                $rules[$identifier . '_achieved'] = 'nullable|integer|min:0';
                $rules[$identifier . '_max'] = 'required|integer|min:1';
            }
        }

        $validated = $request->validate($rules);

        // Validate user has access to the selected branch
        if (!$this->dataAccessService->hasAccessToBranchFromSession($validated['branch_id'])) {
            return back()->withErrors([
                'branch_id' => 'You do not have access to the selected branch.'
            ]);
        }

        // Validate that achieved scores don't exceed max scores (only for evaluation items)
        if (!$isChecklistTemplate) {
            $errors = [];
            foreach ($evaluationItems as $item) {
                $identifier = $item->identifier;
                $achieved = $validated[$identifier . '_achieved'] ?? null;
                $max = $validated[$identifier . '_max'] ?? 0;

                if ($achieved !== null && $achieved > $max) {
                    $errors[$identifier . '_achieved'] = 'Achieved score cannot exceed maximum score.';
                }
            }

            if (!empty($errors)) {
                return back()->withErrors($errors);
            }
        }

        // Get template if it's a checklist
        $template = null;
        if ($isChecklistTemplate) {
            $template = $this->qcTemplateService->getActiveTemplate($validated['branch_id']);
        }

        // Create the evaluation
        $evaluation = new QualityEvaluation([
            'user_id' => Auth::id(),
            'branch_id' => $validated['branch_id'],
            'title' => $validated['title'] ?? 'Quality Control Evaluation',
            'comments' => $validated['comments'],
            'status' => $validated['status'],
            'type' => $isChecklistTemplate ? 'checklist' : 'regular',
            'template_id' => $template ? $template->id : null,
            'extra_points' => $validated['extra_points'] ?? 0,
        ]);

        // Set completion timestamp if status is completed
        if ($validated['status'] === 'completed') {
            $evaluation->completed_at = now();
        }

        $evaluation->save();

        // Handle different form types
        if ($isChecklistTemplate) {
            // Store checklist answers
            if (!empty($validated['answers'])) {
                $this->qcTemplateService->storeAnswers($evaluation, $validated['answers']);
            }

            // Calculate and save checklist score
            $evaluation->load('answers');
            $scoreData = $evaluation->calculateChecklistScore();
            $evaluation->total_score = $scoreData['numerator'];
            $evaluation->max_score = $scoreData['denominator'];
            $evaluation->save();
        } else {
            // Create responses for each evaluation item
            foreach ($evaluationItems as $item) {
                $identifier = $item->identifier;
                $evaluation->responses()->create([
                    'evaluation_item_id' => $item->id,
                    'achieved_score' => $validated[$identifier . '_achieved'] ?? null,
                    'max_score' => $validated[$identifier . '_max'] ?? $item->weight,
                    'weight' => $item->weight,
                ]);
            }

            // Calculate and save total score (only for evaluation items)
            $evaluation->load('responses.evaluationItem');
            $evaluation->total_score = $evaluation->getFinalTotalScore();
            $evaluation->save();
        }

        // Handle photo linking (photos are pre-uploaded with IDs)
        if ($request->has('photo_ids')) {
            $photoIds = $request->input('photo_ids');

            // Handle both array and JSON string formats
            if (is_string($photoIds)) {
                $photoIds = json_decode($photoIds, true);
            }

            if (is_array($photoIds) && !empty($photoIds)) {
                // Get photo_sections array if provided (for checklist evaluations)
                $photoSections = $request->input('photo_sections');

                // Decode photo_sections if it's a JSON string
                if (is_string($photoSections)) {
                    $photoSections = json_decode($photoSections, true);
                }

                $this->linkPhotosToEvaluation($evaluation, $photoIds, $photoSections);
            }
        }

        // Automatically generate PDF after saving
        $this->generatePdfForEvaluation($evaluation);

        if ($evaluation->status === 'completed' && $evaluation->pdf_filename) {
            SendQcReportEmail::dispatch($evaluation->id);
        }

        return redirect()->route('quality-evaluations.show', $evaluation)
            ->with('success', 'Quality evaluation created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(QualityEvaluation $qualityEvaluation): Response
    {
        // Ensure user can only view their own evaluations (Super Admins can view all)
        if ($qualityEvaluation->user_id !== Auth::id() && auth()->user()->group_id !== 1) {
            abort(403);
        }

        // Ensure user has access to the evaluation's branch
        if (!$this->dataAccessService->hasAccessToBranchFromSession($qualityEvaluation->branch_id)) {
            abort(403, 'You do not have access to this evaluation.');
        }

        // Load responses with evaluation items, branch (with brand & country), user, and photos
        $qualityEvaluation->load(['responsesWithItems', 'branch.brand', 'branch.country', 'user', 'photos']);

        // Append the localized_name attribute to the branch
        if ($qualityEvaluation->branch) {
            $qualityEvaluation->branch->append('localized_name');
        }

        // Prepare data based on evaluation type
        $evaluationItems = [];
        $checklistData = null;

        if ($qualityEvaluation->type === 'checklist' && $qualityEvaluation->template_id) {
            // Load checklist template with sections and questions
            $qualityEvaluation->load(['template.sections.questions', 'answers']);

            // Format checklist data for frontend
            $checklistData = $this->formatChecklistData($qualityEvaluation);
        } else {
            // Get evaluation items with actual values from the evaluation
            $evaluationItems = $qualityEvaluation->getEvaluationItems();
        }

        return Inertia::render('QualityEvaluation/Show', [
            'evaluation' => $qualityEvaluation,
            'evaluationItems' => $evaluationItems,
            'checklistData' => $checklistData,
            'locale' => app()->getLocale(),
        ]);
    }

    /**
     * Format checklist data for frontend display.
     */
    private function formatChecklistData(QualityEvaluation $evaluation): array
    {
        $sections = [];
        $answers = [];

        if ($evaluation->template && $evaluation->template->sections) {
            foreach ($evaluation->template->sections as $section) {
                $questions = [];

                if ($section->questions) {
                    foreach ($section->questions as $question) {
                        // Find the answer for this question
                        $answer = $evaluation->answers
                            ->where('question_id', $question->id)
                            ->first();

                        $questions[] = [
                            'id' => $question->id,
                            'name' => $question->name,
                            'name_ar' => $question->name_ar,
                            'localized_name' => $question->localized_name,
                            'q_type' => $question->q_type,
                            'question_type' => $question->question_type,
                            'options' => $question->options ?? [],
                            'score_value' => $question->score_value,
                            'allow_manual_score' => $question->allow_manual_score,
                            'is_required' => $question->is_required,
                            'answer_value' => $answer ? $answer->answer_value : null,
                            'achieved_score' => $answer ? $answer->achieved_score : null,
                            'max_score' => $answer ? $answer->max_score : null,
                        ];

                        // Collect answers for the answers array
                        if ($answer) {
                            $answers[] = [
                                'question_id' => $question->id,
                                'answer_value' => $answer->answer_value,
                            ];
                        }
                    }
                }

                $sections[] = [
                    'id' => $section->id,
                    'name' => $section->name,
                    'name_ar' => $section->name_ar,
                    'localized_name' => $section->localized_name,
                    'questions' => $questions,
                ];
            }
        }

        return [
            'template_id' => $evaluation->template_id,
            'template_name' => $evaluation->template ? $evaluation->template->localized_name : null,
            'answer_type' => $evaluation->template ? $evaluation->template->answer_type : 'Points',
            'sections' => $sections,
            'answers' => $answers,
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(QualityEvaluation $qualityEvaluation): Response
    {
        // Ensure user can only edit their own evaluations (Super Admins can edit all)
        if ($qualityEvaluation->user_id !== Auth::id() && auth()->user()->group_id !== 1) {
            abort(403);
        }

        // Ensure user has access to the evaluation's branch
        if (!$this->dataAccessService->hasAccessToBranchFromSession($qualityEvaluation->branch_id)) {
            abort(403, 'You do not have access to this evaluation.');
        }

        // Prepare data based on evaluation type
        $evaluationItems = [];
        $checklistData = null;

        if ($qualityEvaluation->type === 'checklist' && $qualityEvaluation->template_id) {
            // Load checklist template with sections and questions, plus branch relationships
            $qualityEvaluation->load(['template.sections.questions', 'answers', 'photos', 'branch.brand', 'branch.country']);

            // Format checklist data for frontend
            $checklistData = $this->formatChecklistData($qualityEvaluation);
        } else {
            // Load responses with evaluation items for regular evaluations
            $qualityEvaluation->load(['responsesWithItems', 'photos']);

            // Initialize responses if they don't exist (for legacy evaluations)
            if ($qualityEvaluation->responses->isEmpty()) {
                $qualityEvaluation->initializeResponses();
                $qualityEvaluation->load('responsesWithItems');
            }

            $evaluationItems = EvaluationItem::orderBy('sort_order')->get()->map(function ($item) {
                return [
                    'id' => $item->identifier,
                    'title' => $item->name,
                    'title_ar' => $item->name_ar,
                    'localized_title' => $item->localized_name,
                    'weight' => $item->weight,
                    'exclude_from_score' => $item->exclude_from_score,
                    'overwrite_total_score_if_zero' => $item->overwrite_total_score_if_zero,
                ];
            });
        }

        // Get filtered branches based on user's data access
        $branchQuery = Branch::where([
            "country_id" => '5'
        ])->where('is_catering', 0)
        ->whereIn('brand_id',[3,4,5])
        ->orderBy('brand_id')
        ->orderBy('name');

        // Apply data access filtering
        $this->dataAccessService->applyBranchFilterFromSession($branchQuery, 'id');

        $branches = $branchQuery->get()->map(function ($branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'name_ar' => $branch->name_ar,
                'localized_name' => $branch->localized_name,
            ];
        });

        // Add evaluation items data to the evaluation object for the frontend
        $evaluationData = $qualityEvaluation->toArray();
        if ($qualityEvaluation->type !== 'checklist') {
            $evaluationData['evaluation_items'] = $qualityEvaluation->getEvaluationItems();
        }

        return Inertia::render('QualityEvaluation/Edit', [
            'evaluation' => $evaluationData,
            'evaluationItems' => $evaluationItems,
            'branches' => $branches,
            'checklistData' => $checklistData,
            'locale' => app()->getLocale(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, QualityEvaluation $qualityEvaluation)
    {
        // Ensure user can only update their own evaluations (Super Admins can update all)
        if ($qualityEvaluation->user_id !== Auth::id() && auth()->user()->group_id !== 1) {
            abort(403);
        }

        // Ensure user has access to the evaluation's branch
        if (!$this->dataAccessService->hasAccessToBranchFromSession($qualityEvaluation->branch_id)) {
            abort(403, 'You do not have access to this evaluation.');
        }

        // Determine if this is a checklist evaluation
        $isChecklistEvaluation = $qualityEvaluation->type === 'checklist' && $qualityEvaluation->template_id;
   
        // Build dynamic validation rules
        $rules = [
            'branch_id' => 'required|exists:sma_branches,id',
            'title' => 'nullable|string|max:255',
            'comments' => 'nullable|string|max:5000',
            'status' => ['required', Rule::in(['draft', 'completed'])],
            'extra_points' => 'nullable|integer|min:-5|max:5',
            'photo_ids' => 'nullable|string', // JSON array of photo IDs
            'photo_sections' => 'nullable|string', // JSON array of section IDs for photos
            'photos_to_delete' => 'nullable|string', // JSON array of photo IDs to delete
        ];

 

        if ($isChecklistEvaluation) {
            // For checklist evaluations, validate answers
            $rules['answers'] = 'nullable|array';
            $rules['answers.*'] = 'nullable|string|max:1000';
        } else {
            // For regular evaluations, get evaluation items and add validation rules
            $evaluationItems = EvaluationItem::all();

            foreach ($evaluationItems as $item) {
                // Accept both identifier-based and id-based field names
                $identifier = $item->identifier;
                $rules[$identifier . '_achieved'] = 'nullable|numeric|min:0';
                $rules[$identifier . '_max'] = 'nullable|numeric|min:1';

                // Also accept id-based field names for compatibility
                $rules[$item->id . '_achieved'] = 'nullable|numeric|min:0';
                $rules[$item->id . '_max'] = 'nullable|numeric|min:1';
            }
        }


        $validated = $request->validate($rules);


        // Validate user has access to the selected branch
        if (!$this->dataAccessService->hasAccessToBranchFromSession($validated['branch_id'])) {
            return back()->withErrors([
                'branch_id' => 'You do not have access to the selected branch.'
            ]);
        }

        // For regular evaluations, validate that achieved scores don't exceed max scores
        if (!$isChecklistEvaluation) {
            $evaluationItems = EvaluationItem::all();
            $errors = [];
            foreach ($evaluationItems as $item) {
                $identifier = $item->identifier;
                $achieved = $validated[$identifier . '_achieved'] ?? null;
                $max = $validated[$identifier . '_max'] ?? 0;

                if ($achieved !== null && $achieved > $max) {
                    $errors[$identifier . '_achieved'] = 'Achieved score cannot exceed maximum score.';
                }
            }

            if (!empty($errors)) {
                return back()->withErrors($errors);
            }
        }

        // Update the evaluation basic fields
        $qualityEvaluation->fill([
            'branch_id' => $validated['branch_id'],
            'title' => $validated['title'] ?? $qualityEvaluation->title,
            'comments' => $validated['comments'],
            'status' => $validated['status'],
            'extra_points' => $validated['extra_points'] ?? 0,
        ]);

        // Set completion timestamp if status changed to completed
        if ($validated['status'] === 'completed' && $qualityEvaluation->completed_at === null) {
            $qualityEvaluation->completed_at = now();
        } elseif ($validated['status'] === 'draft') {
            $qualityEvaluation->completed_at = null;
        }

        if ($isChecklistEvaluation) {
            // Update checklist answers
            $this->qcTemplateService->storeAnswers($qualityEvaluation, $validated['answers'] ?? []);

            // Recalculate checklist score
            $scoreData = $qualityEvaluation->calculateChecklistScore();
            $qualityEvaluation->total_score = $scoreData['numerator'];
            $qualityEvaluation->max_score = $scoreData['denominator'];
        } else {
            // Update responses for each evaluation item
            $qualityEvaluation->load('responsesWithItems');

            // Initialize responses if they don't exist (for legacy evaluations)
            if ($qualityEvaluation->responses->isEmpty()) {
                $qualityEvaluation->initializeResponses();
                $qualityEvaluation->load('responsesWithItems');
            }

            $evaluationItems = EvaluationItem::all();
            foreach ($evaluationItems as $item) {
                $identifier = $item->identifier;
                $response = $qualityEvaluation->responses()->where('evaluation_item_id', $item->id)->first();

                if ($response) {
                    // Try to get achieved score from either identifier or id format
                    $achievedScore = $validated[$identifier . '_achieved'] ?? $validated[$item->id . '_achieved'] ?? null;
                    $maxScore = $validated[$identifier . '_max'] ?? $validated[$item->id . '_max'] ?? $item->weight;

                    $response->update([
                        'achieved_score' => $achievedScore,
                        'max_score' => $maxScore,
                        'weight' => $item->weight, // Update weight to current item weight
                    ]);
                }
            }

            // Recalculate total score
            $qualityEvaluation->load('responses.evaluationItem');
            $qualityEvaluation->total_score = $qualityEvaluation->getFinalTotalScore();
        }

        $qualityEvaluation->save();

        // Handle photo deletions
        if ($request->has('photos_to_delete')) {

            $photosToDelete = $request->input('photos_to_delete');

            // Handle both array and JSON string formats
            if (is_string($photosToDelete)) {
                $photosToDelete = json_decode($photosToDelete, true);
            }

            if (is_array($photosToDelete)) {
                foreach ($photosToDelete as $photoId) {
                    $photo = QualityEvaluationPhoto::find($photoId);
                    if ($photo && $photo->quality_evaluation_id === $qualityEvaluation->id) {
                        \Log::info('Deleting photo', ['photo_id' => $photoId, 'file_path' => $photo->file_path]);
                        $photo->delete(); // This will also delete the file via the model's boot method
                    }
                }
            }
        }

        // Handle photo linking (photos are pre-uploaded with IDs)
        if ($request->has('photo_ids')) {
            $photoIds = $request->input('photo_ids');

            // Handle both array and JSON string formats
            if (is_string($photoIds)) {
                $photoIds = json_decode($photoIds, true);
            }

            if (is_array($photoIds) && !empty($photoIds)) {
                // Get photo_sections array if provided (for checklist evaluations)
                $photoSections = $request->input('photo_sections');

                // Decode photo_sections if it's a JSON string
                if (is_string($photoSections)) {
                    $photoSections = json_decode($photoSections, true);
                }

                $this->linkPhotosToEvaluation($qualityEvaluation, $photoIds, $photoSections);
            }
        }

        // Automatically generate PDF after updating
        $this->generatePdfForEvaluation($qualityEvaluation);

        return redirect()->route('quality-evaluations.show', $qualityEvaluation)
            ->with('success', 'Quality evaluation updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QualityEvaluation $qualityEvaluation)
    {
        // Ensure user can only delete their own evaluations
        if ($qualityEvaluation->user_id !== Auth::id()) {
            abort(403);
        }

        // Ensure user has access to the evaluation's branch
        if (!$this->dataAccessService->hasAccessToBranchFromSession($qualityEvaluation->branch_id)) {
            abort(403, 'You do not have access to this evaluation.');
        }

        $qualityEvaluation->delete();

        return redirect()->route('quality-evaluations.index')
            ->with('success', 'Quality evaluation deleted successfully.');
    }

    /**
     * Export quality evaluation as PDF.
     */
    public function exportPdf(QualityEvaluation $qualityEvaluation)
    {
        // Ensure user can only export their own evaluations (Super Admins can export all)
        if ($qualityEvaluation->user_id !== Auth::id() && auth()->user()->group_id !== 1) {
            abort(403);
        }

        // Ensure user has access to the evaluation's branch
        if (!$this->dataAccessService->hasAccessToBranchFromSession($qualityEvaluation->branch_id)) {
            abort(403, 'You do not have access to this evaluation.');
        }

        try {
            // Load necessary relationships based on evaluation type
            if ($qualityEvaluation->type === 'checklist') {
                // Load checklist-specific relationships
                $qualityEvaluation->load(['template.sections.questions', 'answers', 'branch.brand', 'user', 'photos']);

                // Append the localized_name attribute to the branch
                if ($qualityEvaluation->branch) {
                    $qualityEvaluation->branch->append('localized_name');
                }

                // Generate checklist PDF
                $pdfService = new \App\Services\QualityEvaluationPdfService();
                $pdfResult = $pdfService->generateChecklistPdf($qualityEvaluation);
            } else {
                // Load regular evaluation relationships
                $qualityEvaluation->load(['responsesWithItems', 'branch.brand', 'user', 'photos']);

                // Append the localized_name attribute to the branch
                if ($qualityEvaluation->branch) {
                    $qualityEvaluation->branch->append('localized_name');
                }

                // Get evaluation items with scores
                $evaluationItems = $qualityEvaluation->getEvaluationItems();

                // Validate evaluation is complete enough for PDF generation
                if (empty($evaluationItems)) {
                    return back()->with('error', 'No evaluation items found. Cannot generate PDF.');
                }

                // Generate regular PDF
                $pdfService = new \App\Services\QualityEvaluationPdfService();
                $pdfResult = $pdfService->generatePdf($qualityEvaluation, $evaluationItems);
            }

            $pdfPath = $pdfResult['path'];
            $filename = $pdfResult['filename'];

            // Verify file exists and is readable
            if (!file_exists($pdfPath) || !is_readable($pdfPath)) {
                return back()->with('error', 'PDF file could not be generated or is not accessible.');
            }

            // Store PDF filename in database for future reference
            $qualityEvaluation->update(['pdf_filename' => $filename]);

            // Return PDF download response (keep file for permanent access)
            return response()->download($pdfPath, "evaluation_{$qualityEvaluation->id}.pdf", [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (\InvalidArgumentException $e) {
            return back()->with('error', 'Invalid data: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('PDF generation failed for evaluation ' . $qualityEvaluation->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to generate PDF. Please try again or contact support if the problem persists.');
        }
    }

    /**
     * Download PDF from server storage.
     */
    public function downloadPdf(QualityEvaluation $qualityEvaluation)
    {
        // Ensure user can only download their own evaluations (Super Admins can download all)
        if ($qualityEvaluation->user_id !== Auth::id() && auth()->user()->group_id !== 1) {
            abort(403);
        }

        // Ensure user has access to the evaluation's branch
        if (!$this->dataAccessService->hasAccessToBranchFromSession($qualityEvaluation->branch_id)) {
            abort(403, 'You do not have access to this evaluation.');
        }

        // Check if PDF exists, if not generate it
        if (!$qualityEvaluation->hasPdf()) {
            try {
                $pdfService = new \App\Services\QualityEvaluationPdfService();
                $pdfResult = null;

                // Generate PDF based on evaluation type
                if ($qualityEvaluation->type === 'checklist') {
                    // Load checklist-specific relationships
                    $qualityEvaluation->load(['template.sections.questions', 'answers', 'branch.brand', 'user', 'photos']);

                    // Append the localized_name attribute to the branch
                    if ($qualityEvaluation->branch) {
                        $qualityEvaluation->branch->append('localized_name');
                    }

                    // Generate checklist PDF
                    $pdfResult = $pdfService->generateChecklistPdf($qualityEvaluation);
                } else {
                    // Load regular evaluation relationships
                    $qualityEvaluation->load(['responsesWithItems', 'branch.brand', 'user', 'photos']);

                    // Append the localized_name attribute to the branch
                    if ($qualityEvaluation->branch) {
                        $qualityEvaluation->branch->append('localized_name');
                    }

                    // Get evaluation items with scores
                    $evaluationItems = $qualityEvaluation->getEvaluationItems();

                    // Validate evaluation is complete enough for PDF generation
                    if (empty($evaluationItems)) {
                        return back()->with('error', 'No evaluation items found. Cannot generate PDF.');
                    }

                    // Generate regular PDF
                    $pdfResult = $pdfService->generatePdf($qualityEvaluation, $evaluationItems);
                }

                if (!$pdfResult) {
                    return back()->with('error', 'PDF could not be generated.');
                }

                $pdfPath = $pdfResult['path'];
                $filename = $pdfResult['filename'];

                // Verify file exists and is readable
                if (!file_exists($pdfPath) || !is_readable($pdfPath)) {
                    return back()->with('error', 'PDF file could not be generated or is not accessible.');
                }

                // Store PDF filename in database for future reference
                $qualityEvaluation->update(['pdf_filename' => $filename]);

            } catch (\InvalidArgumentException $e) {
                return back()->with('error', 'Invalid data: ' . $e->getMessage());
            } catch (\Exception $e) {
                // Log the error for debugging
                \Log::error('PDF generation failed for evaluation ' . $qualityEvaluation->id, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return back()->with('error', 'Failed to generate PDF. Please try again or contact support if the problem persists.');
            }
        }

        // Build the file path
        $filePath = storage_path('app/public/pdfs/' . $qualityEvaluation->pdf_filename);

        // Check if file exists
        if (!file_exists($filePath)) {
            return back()->with('error', 'PDF file not found on server.');
        }

        // Return PDF file response to open in browser (inline display)
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="evaluation_' . $qualityEvaluation->id . '.pdf"'
        ]);
    }

    /**
     * Serve a PDF file directly from storage.
     */
    public function servePdf(QualityEvaluation $qualityEvaluation, string $filename)
    {
        // Ensure user can only access their own evaluations (Super Admins can access all)
        if ($qualityEvaluation->user_id !== Auth::id() && auth()->user()->group_id !== 1) {
            abort(403);
        }

        // Ensure user has access to the evaluation's branch
        if (!$this->dataAccessService->hasAccessToBranchFromSession($qualityEvaluation->branch_id)) {
            abort(403, 'You do not have access to this evaluation.');
        }

        // Validate filename format (security check)
        if (!preg_match('/^evaluation_' . $qualityEvaluation->id . '_\d+\.pdf$/', $filename)) {
            abort(404);
        }

        // Build the file path
        $filePath = storage_path('app/public/pdfs/' . $filename);

        // Check if file exists
        if (!file_exists($filePath)) {
            abort(404);
        }

        // Return the file response
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="evaluation_' . $qualityEvaluation->id . '.pdf"'
        ]);
    }

    /**
     * Serve a photo file directly from storage.
     */
    public function servePhoto(QualityEvaluationPhoto $photo)
    {
        // Check if user has access to this photo's evaluation
        $evaluation = $photo->qualityEvaluation;

        // Only apply data access filtering if photo is linked to an evaluation
        if ($evaluation) {
            // Apply data access filtering
            if ($evaluation->user_id !== Auth::id()) {
                $query = QualityEvaluation::where('id', $evaluation->id);
                $this->dataAccessService->applyBranchFilterFromSession($query);

                if (!$query->exists()) {
                    abort(403);
                }
            }
        }

        // Check if file exists
        if (!$photo->fileExists()) {
            abort(404);
        }

        // Get the file path
        $filePath = Storage::disk('public')->path($photo->file_path);

        // Return the file response
        return response()->file($filePath, [
            'Content-Type' => $photo->mime_type,
            'Content-Disposition' => 'inline; filename="' . $photo->original_filename . '"'
        ]);
    }

    /**
     * Handle photo uploads for a quality evaluation.
     * Supports both regular evaluations and section-based photos for checklist evaluations.
     */
    private function handlePhotoUploads(array $photos, QualityEvaluation $evaluation, ?array $photoSections = null): void
    {
        foreach ($photos as $index => $photo) {
            if ($photo instanceof UploadedFile && $photo->isValid()) {
                // Generate unique filename
                $filename = Str::uuid() . '.' . $photo->getClientOriginalExtension();

                // Store in quality-evaluations/{evaluation_id}/ directory
                $path = "quality-evaluations/{$evaluation->id}";
                $filePath = $photo->storeAs($path, $filename, 'public');

                // Get section_id if provided (for checklist evaluations)
                $sectionId = null;
                if ($photoSections && isset($photoSections[$index])) {
                    $sectionId = $photoSections[$index];
                }

                // Create photo record
                QualityEvaluationPhoto::create([
                    'quality_evaluation_id' => $evaluation->id,
                    'section_id' => $sectionId,
                    'filename' => $filename,
                    'original_filename' => $photo->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_size' => $photo->getSize(),
                    'mime_type' => $photo->getMimeType(),
                    'uploaded_at' => now(),
                ]);
            }
        }
    }

    /**
     * Delete a specific photo from a quality evaluation.
     */
    public function deletePhoto(QualityEvaluation $qualityEvaluation, QualityEvaluationPhoto $photo)
    {
        // Ensure user can only delete photos from their own evaluations
        if ($qualityEvaluation->user_id !== Auth::id()) {
            abort(403);
        }

        // Ensure user has access to the evaluation's branch
        if (!$this->dataAccessService->hasAccessToBranchFromSession($qualityEvaluation->branch_id)) {
            abort(403, 'You do not have access to this evaluation.');
        }

        // Ensure the photo belongs to this evaluation
        if ($photo->quality_evaluation_id !== $qualityEvaluation->id) {
            abort(404);
        }

        // Delete the photo (this will also delete the file due to model boot method)
        $photo->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Automatically generate PDF for an evaluation.
     */
    private function generatePdfForEvaluation(QualityEvaluation $evaluation): void
    {
        try {
            // Append the localized_name attribute to the branch
            if ($evaluation->branch) {
                $evaluation->branch->append('localized_name');
            }

            $pdfService = new \App\Services\QualityEvaluationPdfService();
            $pdfResult = null;

            // Generate PDF based on evaluation type
            if ($evaluation->type === 'checklist') {
                // Load checklist-specific relationships
                $evaluation->load(['template.sections.questions', 'answers', 'branch.brand', 'user', 'photos']);

                // Generate checklist PDF
                $pdfResult = $pdfService->generateChecklistPdf($evaluation);
            } else {
                // Load regular evaluation relationships
                $evaluation->load(['responsesWithItems', 'branch.brand', 'user', 'photos']);

                // Get evaluation items with scores
                $evaluationItems = $evaluation->getEvaluationItems();

                // Only generate PDF if we have evaluation items
                if (!empty($evaluationItems)) {
                    // Generate regular PDF
                    $pdfResult = $pdfService->generatePdf($evaluation, $evaluationItems);
                }
            }

            // Store PDF filename in database for future reference
            if ($pdfResult) {
                $evaluation->update(['pdf_filename' => $pdfResult['filename']]);
            }
        } catch (\Exception $e) {
            // Log the error but don't interrupt the main flow
            \Log::error('Automatic PDF generation failed for evaluation ' . $evaluation->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * API endpoint: Check if a branch has an active QC template.
     */
    public function checkTemplate(Request $request)
    {
        $branchId = $request->query('branch_id');

        if (!$branchId) {
            return response()->json(['error' => 'branch_id is required'], 400);
        }

        // Verify user has access to the branch
        if (!$this->dataAccessService->hasAccessToBranchFromSession($branchId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $hasTemplate = $this->qcTemplateService->hasActiveTemplate($branchId);

        return response()->json([
            'has_template' => $hasTemplate,
        ]);
    }

    /**
     * API endpoint: Get template data for a branch.
     */
    public function getTemplate(Request $request)
    {
        $branchId = $request->query('branch_id');

        if (!$branchId) {
            return response()->json(['error' => 'branch_id is required'], 400);
        }

        // Verify user has access to the branch
        if (!$this->dataAccessService->hasAccessToBranchFromSession($branchId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get branch to check country_id
        $branch = Branch::find($branchId);
        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }

        $template = $this->qcTemplateService->getTemplateForBranch($branchId);

        if (!$template) {
            return response()->json(['error' => 'No active template found'], 404);
        }

        return response()->json([
            ...$template,
            'branch_country_id' => $branch->country_id,
            'branch_brand_id' => $branch->brand_id,
            'form_type' => 'CHECKLIST',
        ]);
    }

    /**
     * API endpoint: Upload a photo immediately and return its ID.
     * This endpoint handles immediate photo uploads without linking to an evaluation.
     * The photo ID is returned and can be used later when submitting the form.
     */
    public function uploadPhoto(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:50000', // Max 5MB
        ]);

        try {
            $photo = $request->file('photo');

            // Generate unique filename
            $filename = Str::uuid() . '.' . $photo->getClientOriginalExtension();

            // Store in temporary uploads directory (not linked to any evaluation yet)
            $path = 'quality-evaluations/temp';
            $filePath = $photo->storeAs($path, $filename, 'public');

            // Create a temporary photo record (not linked to any evaluation)
            $photoRecord = QualityEvaluationPhoto::create([
                'quality_evaluation_id' => null, // Temporary, will be linked later
                'section_id' => null,
                'filename' => $filename,
                'original_filename' => $photo->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $photo->getSize(),
                'mime_type' => $photo->getMimeType(),
                'uploaded_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'photo_id' => $photoRecord->id,
                'url' => route('quality-evaluations.photos.serve', $photoRecord->id),
                'filename' => $filename,
            ]);
        } catch (\Exception $e) {
            \Log::error('Photo upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to upload photo. Please try again.'
            ], 500);
        }
    }

    /**
     * Link pre-uploaded photos to a quality evaluation.
     * This method is called during form submission to link temporary photos to the evaluation.
     */
    private function linkPhotosToEvaluation(QualityEvaluation $evaluation, array $photoIds, ?array $photoSections = null): void
    {
        foreach ($photoIds as $index => $photoId) {
            // Find the temporary photo record
            $photo = QualityEvaluationPhoto::find($photoId);

            if ($photo && $photo->quality_evaluation_id === null) {
                // Get section_id if provided (for checklist evaluations)
                $sectionId = null;
                if ($photoSections && isset($photoSections[$index])) {
                    $sectionId = $photoSections[$index];
                }

                // Link the photo to the evaluation
                $photo->update([
                    'quality_evaluation_id' => $evaluation->id,
                    'section_id' => $sectionId,
                ]);
            }
        }
    }
}
