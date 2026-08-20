<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    public $timestamps = false;
    protected $table = "sma_users";
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'group_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // Remove 'password' => 'hashed' to use custom hashing
        ];
    }


    public function getNameAttribute()
    {
        return $this->first_name." ".$this->last_name;
    }

    /**
     * Get the user's access restrictions.
     */
    public function userAccess(): HasMany
    {
        return $this->hasMany(UserAccess::class);
    }

    /**
     * Get the user's group.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the quality evaluations created by this user.
     */
    public function qualityEvaluations(): HasMany
    {
        return $this->hasMany(QualityEvaluation::class);
    }

    /**
     * Check if user has a specific permission.
     *
     * @param string $subModule
     * @param string $action
     * @return bool
     */
    public function hasPermission(string $subModule, string $action): bool
    {
        if (!$this->group_id) {
            return false;
        }

        return GroupPermission::hasPermission($this->group_id, $subModule, $action);
    }

    /**
     * Get user's permissions for a specific sub-module.
     *
     * @param string $subModule
     * @return array
     */
    public function getPermissions(string $subModule): array
    {
        if (!$this->group_id) {
            return [];
        }

        $permissions = GroupPermission::getPermissions($this->group_id, $subModule);

        if ($permissions->isEmpty()) {
            return [];
        }

        $actions = [];
        foreach ($permissions as $permission) {
            // If actions are comma-separated in one row
            if (strpos($permission->action, ',') !== false) {
                $actions = array_merge($actions, $permission->getActionsArray());
            } else {
                // If each action is in a separate row
                $actions[] = trim($permission->action);
            }
        }

        return array_values(array_unique($actions));
    }

    /**
     * Check if user has access to a specific branch.
     *
     * @param int $branchId
     * @return bool
     */
    public function hasAccessToBranch(int $branchId): bool
    {
        return UserAccess::hasAccessToBranch($this->id, $branchId);
    }

    /**
     * Get allowed branch IDs for this user.
     *
     * @return array|null Returns null for unrestricted access
     */
    public function getAllowedBranchIds(): ?array
    {
        return UserAccess::getAllowedBranchIds($this->id);
    }

    /**
     * Check if user has unrestricted branch access.
     *
     * @return bool
     */
    public function hasUnrestrictedBranchAccess(): bool
    {
        return UserAccess::hasUnrestrictedBranchAccess($this->id);
    }

    /**
     * Get Quality Evaluation specific permissions.
     *
     * @return array
     */
    public function getQualityEvaluationPermissions(): array
    {
        return $this->getPermissions('quality_evaluations');
    }

    /**
     * Check if user can create Quality Evaluations.
     *
     * @return bool
     */
    public function canCreateQualityEvaluation(): bool
    {
        return $this->hasPermission('quality_evaluations', 'create');
    }

    /**
     * Check if user can view Quality Evaluations.
     *
     * @return bool
     */
    public function canViewQualityEvaluation(): bool
    {
        return $this->hasPermission('quality_evaluations', 'view');
    }

    /**
     * Check if user can edit Quality Evaluations.
     *
     * @return bool
     */
    public function canEditQualityEvaluation(): bool
    {
        return $this->hasPermission('quality_evaluations', 'edit');
    }

    /**
     * Check if user can delete Quality Evaluations.
     *
     * @return bool
     */
    public function canDeleteQualityEvaluation(): bool
    {
        return $this->hasPermission('quality_evaluations', 'delete');
    }
}
