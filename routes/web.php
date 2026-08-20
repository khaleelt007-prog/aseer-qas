<?php

use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QualityEvaluationController;
use App\Http\Controllers\QualityEvaluationFollowUpController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\FollowUpController as SuperAdminFollowUpController;
use App\Http\Controllers\SuperAdmin\QualityEvaluationController as SuperAdminQualityEvaluationController;
use App\Http\Controllers\SuperAdmin\QcReportEmailSettingController as SuperAdminQcReportEmailSettingController;
use App\Http\Controllers\SuperAdmin\BranchVisitsReportController as SuperAdminBranchVisitsReportController;
use App\Http\Controllers\SuperAdmin\TopEvaluatorReportController as SuperAdminTopEvaluatorReportController;
use App\Http\Controllers\SuperAdmin\TemplateSetupController as SuperAdminTemplateSetupController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;



Route::get('/', function () {
    return redirect()->route('dashboard');
})->middleware(['auth', 'verified']);



Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'locale' => app()->getLocale(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');



// Language switching route
Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

// Photo management routes
Route::get('quality-evaluations/photos/{photo}', [QualityEvaluationController::class, 'servePhoto'])
    ->name('quality-evaluations.photos.serve');

Route::get('quality-evaluations/{qualityEvaluation}/download-pdf', [QualityEvaluationController::class, 'downloadPdf'])
    ->name('quality-evaluations.download-pdf');

// QC Template API routes
Route::get('api/quality-evaluations/check-template', [QualityEvaluationController::class, 'checkTemplate'])
    ->middleware('auth')
    ->name('quality-evaluations.api.check-template');

Route::get('api/quality-evaluations/get-template', [QualityEvaluationController::class, 'getTemplate'])
    ->middleware('auth')
    ->name('quality-evaluations.api.get-template');

// Photo upload API route (immediate upload without evaluation linking)
Route::post('api/quality-evaluations/photos/upload', [QualityEvaluationController::class, 'uploadPhoto'])
    ->middleware('auth')
    ->name('quality-evaluations.api.photos.upload');

Route::middleware('auth')->group(function () {
    // Quality Evaluation routes with permission middleware
    Route::get('quality-evaluation-follow-ups', [QualityEvaluationFollowUpController::class, 'index'])
        ->middleware('quality.permission:view')
        ->name('quality-evaluation-follow-ups.index');

    Route::get('quality-evaluation-follow-ups/{qualityEvaluation}', [QualityEvaluationFollowUpController::class, 'show'])
        ->middleware('quality.permission:view')
        ->name('quality-evaluation-follow-ups.show');

    Route::post('quality-evaluation-follow-ups/{qualityEvaluation}/answers/{answer}/deadline', [QualityEvaluationFollowUpController::class, 'setDeadline'])
        ->middleware('quality.permission:edit')
        ->name('quality-evaluation-follow-ups.answers.deadline');

    Route::post('quality-evaluation-follow-ups/{qualityEvaluation}/answers/{answer}/comments', [QualityEvaluationFollowUpController::class, 'storeComment'])
        ->middleware('quality.permission:edit')
        ->name('quality-evaluation-follow-ups.answers.comments.store');

    Route::post('quality-evaluation-follow-ups/{qualityEvaluation}/answers/{answer}/mark-solved', [QualityEvaluationFollowUpController::class, 'markSolved'])
        ->middleware('quality.permission:edit')
        ->name('quality-evaluation-follow-ups.answers.mark-solved');

    Route::post('quality-evaluation-follow-ups/{qualityEvaluation}/answers/{answer}/mark-skipped', [QualityEvaluationFollowUpController::class, 'markSkipped'])
        ->middleware('quality.permission:edit')
        ->name('quality-evaluation-follow-ups.answers.mark-skipped');

    Route::get('quality-evaluations', [QualityEvaluationController::class, 'index'])
        ->middleware('quality.permission:view')
        ->name('quality-evaluations.index');

    Route::get('quality-evaluations/create', [QualityEvaluationController::class, 'create'])
        ->middleware('quality.permission:create')
        ->name('quality-evaluations.create');

    Route::post('quality-evaluations', [QualityEvaluationController::class, 'store'])
        ->middleware('quality.permission:create')
        ->name('quality-evaluations.store');

    Route::get('quality-evaluations/{qualityEvaluation}', [QualityEvaluationController::class, 'show'])
        ->middleware('quality.permission:view')
        ->name('quality-evaluations.show');

    Route::get('quality-evaluations/{qualityEvaluation}/edit', [QualityEvaluationController::class, 'edit'])
        ->middleware('quality.permission:edit')
        ->name('quality-evaluations.edit');

    Route::post('quality-evaluations/{qualityEvaluation}', [QualityEvaluationController::class, 'update'])
        ->middleware('quality.permission:edit')
        ->name('quality-evaluations.update');

    Route::delete('quality-evaluations/{qualityEvaluation}', [QualityEvaluationController::class, 'destroy'])
        ->middleware('quality.permission:delete')
        ->name('quality-evaluations.destroy');



    Route::delete('quality-evaluations/{qualityEvaluation}/photos/{photo}', [QualityEvaluationController::class, 'deletePhoto'])
        ->middleware('quality.permission:edit')
        ->name('quality-evaluations.photos.destroy');

    // PDF export route
    Route::get('quality-evaluations/{qualityEvaluation}/export-pdf', [QualityEvaluationController::class, 'exportPdf'])
        ->middleware('quality.permission:view')
        ->name('quality-evaluations.export-pdf');

    // PDF download route (from server storage)


    // PDF direct access route
    Route::get('quality-evaluations/{qualityEvaluation}/pdf/{filename}', [QualityEvaluationController::class, 'servePdf'])
        ->name('quality-evaluations.serve-pdf');
});

// Super Admin panel (group_id === 1 only)
Route::middleware(['auth', 'super-admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('dashboard', [SuperAdminDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('quality-evaluations', [SuperAdminQualityEvaluationController::class, 'index'])
            ->name('quality-evaluations.index');

        Route::get('quality-evaluations/export', [SuperAdminQualityEvaluationController::class, 'export'])
            ->name('quality-evaluations.export');

        Route::get('follow-ups', [SuperAdminFollowUpController::class, 'index'])
            ->name('follow-ups.index');

        Route::get('follow-ups/{id}', [SuperAdminFollowUpController::class, 'show'])
            ->whereNumber('id')
            ->name('follow-ups.show');

        Route::resource('template-setup', SuperAdminTemplateSetupController::class)
            ->parameters(['template-setup' => 'template'])
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('qc-report-email-settings', [SuperAdminQcReportEmailSettingController::class, 'index'])
            ->name('qc-report-email-settings.index');
        Route::post('qc-report-email-settings', [SuperAdminQcReportEmailSettingController::class, 'store'])
            ->name('qc-report-email-settings.store');

        Route::post('template-setup/{template}/sections', [SuperAdminTemplateSetupController::class, 'storeSection'])
            ->whereNumber('template')
            ->name('template-setup.sections.store');
        Route::put('template-setup/{template}/sections/{section}', [SuperAdminTemplateSetupController::class, 'updateSection'])
            ->whereNumber(['template', 'section'])
            ->name('template-setup.sections.update');
        Route::delete('template-setup/{template}/sections/{section}', [SuperAdminTemplateSetupController::class, 'destroySection'])
            ->whereNumber(['template', 'section'])
            ->name('template-setup.sections.destroy');
        Route::post('template-setup/sections/{section}/questions', [SuperAdminTemplateSetupController::class, 'storeQuestion'])
            ->whereNumber('section')
            ->name('template-setup.questions.store');
        Route::put('template-setup/sections/{section}/questions/{question}', [SuperAdminTemplateSetupController::class, 'updateQuestion'])
            ->whereNumber(['section', 'question'])
            ->name('template-setup.questions.update');
        Route::delete('template-setup/sections/{section}/questions/{question}', [SuperAdminTemplateSetupController::class, 'destroyQuestion'])
            ->whereNumber(['section', 'question'])
            ->name('template-setup.questions.destroy');

        Route::get('reports/top-evaluators', [SuperAdminTopEvaluatorReportController::class, 'index'])
            ->name('reports.top-evaluators.index');

        Route::get('reports/top-evaluators/{user}/evaluations', [SuperAdminTopEvaluatorReportController::class, 'evaluations'])
            ->whereNumber('user')
            ->name('reports.top-evaluators.evaluations');

        Route::get('reports/branch-visits', [SuperAdminBranchVisitsReportController::class, 'index'])
            ->name('reports.branch-visits.index');

        Route::get('reports/branch-visits/{branchId}/evaluations', [SuperAdminBranchVisitsReportController::class, 'evaluations'])
            ->whereNumber('branchId')
            ->name('reports.branch-visits.evaluations');
    });

require __DIR__.'/auth.php';
