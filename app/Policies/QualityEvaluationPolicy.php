<?php

namespace App\Policies;

use App\Models\User;
use App\Models\QualityEvaluation;
use App\Services\PermissionService;
use App\Services\DataAccessService;

class QualityEvaluationPolicy
{
    protected $permissionService;
    protected $dataAccessService;

    public function __construct(PermissionService $permissionService, DataAccessService $dataAccessService)
    {
        $this->permissionService = $permissionService;
        $this->dataAccessService = $dataAccessService;
    }

    /**
     * Determine whether the user can view any quality evaluations.
     */
    public function viewAny(User $user): bool
    {
        return $this->permissionService->hasPermission($user, 'quality_evaluations', 'view');
    }

    /**
     * Determine whether the user can view the quality evaluation.
     */
    public function view(User $user, QualityEvaluation $qualityEvaluation): bool
    {
        // Check if user has view permission
        if (!$this->permissionService->hasPermission($user, 'quality_evaluations', 'view')) {
            return false;
        }

        // Check if user owns the evaluation
        if ($qualityEvaluation->user_id !== $user->id) {
            return false;
        }

        // Check if user has access to the evaluation's branch
        return $this->dataAccessService->hasAccessToBranch($user, $qualityEvaluation->branch_id);
    }

    /**
     * Determine whether the user can create quality evaluations.
     */
    public function create(User $user): bool
    {
        return $this->permissionService->hasPermission($user, 'quality_evaluations', 'create');
    }

    /**
     * Determine whether the user can update the quality evaluation.
     */
    public function update(User $user, QualityEvaluation $qualityEvaluation): bool
    {
        // Check if user has edit permission
        if (!$this->permissionService->hasPermission($user, 'quality_evaluations', 'edit')) {
            return false;
        }

        // Check if user owns the evaluation
        if ($qualityEvaluation->user_id !== $user->id) {
            return false;
        }

        // Check if user has access to the evaluation's branch
        return $this->dataAccessService->hasAccessToBranch($user, $qualityEvaluation->branch_id);
    }

    /**
     * Determine whether the user can delete the quality evaluation.
     */
    public function delete(User $user, QualityEvaluation $qualityEvaluation): bool
    {
        // Check if user has delete permission
        if (!$this->permissionService->hasPermission($user, 'quality_evaluations', 'delete')) {
            return false;
        }

        // Check if user owns the evaluation
        if ($qualityEvaluation->user_id !== $user->id) {
            return false;
        }

        // Check if user has access to the evaluation's branch
        return $this->dataAccessService->hasAccessToBranch($user, $qualityEvaluation->branch_id);
    }

    /**
     * Determine whether the user can restore the quality evaluation.
     */
    public function restore(User $user, QualityEvaluation $qualityEvaluation): bool
    {
        return $this->delete($user, $qualityEvaluation);
    }

    /**
     * Determine whether the user can permanently delete the quality evaluation.
     */
    public function forceDelete(User $user, QualityEvaluation $qualityEvaluation): bool
    {
        return $this->delete($user, $qualityEvaluation);
    }

    /**
     * Determine whether the user can manage photos for the quality evaluation.
     */
    public function managePhotos(User $user, QualityEvaluation $qualityEvaluation): bool
    {
        // Use the same logic as update since photo management is part of editing
        return $this->update($user, $qualityEvaluation);
    }

    /**
     * Determine whether the user can access a specific branch for quality evaluations.
     */
    public function accessBranch(User $user, int $branchId): bool
    {
        return $this->dataAccessService->hasAccessToBranch($user, $branchId);
    }
}
