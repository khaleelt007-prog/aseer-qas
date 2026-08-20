<?php

namespace App\Services;

use App\Models\User;
use App\Models\GroupPermission;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    const CACHE_PREFIX = 'user_permissions_';
    const CACHE_TTL = 3600; // 1 hour
    const SESSION_KEY = 'user_permissions';

    /**
     * Get user permissions for a specific sub-module.
     *
     * @param User $user
     * @param string $subModule
     * @return array
     */
    public function getUserPermissions(User $user, string $subModule): array
    {
        $cacheKey = self::CACHE_PREFIX . $user->id . '_' . $subModule;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $subModule) {
            if (!$user->group_id) {
                return [];
            }

            // Get all permissions for this group and sub-module (may be multiple rows)
            $permissions = GroupPermission::where('gorup', $user->group_id)
                                        ->where('sub_module', $subModule)
                                        ->get();

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
        });
    }

    /**
     * Check if user has a specific permission.
     *
     * @param User $user
     * @param string $subModule
     * @param string $action
     * @return bool
     */
    public function hasPermission(User $user, string $subModule, string $action): bool
    {
    
        $permissions = $this->getUserPermissions($user, $subModule);
        return in_array($action, $permissions);
    }

    /**
     * Get all permissions for a user and cache them in session.
     *
     * @param User $user
     * @return array
     */
    public function getAllUserPermissions(User $user): array
    {
        $cacheKey = self::CACHE_PREFIX . $user->id . '_all';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            if (!$user->group_id) {
                return [];
            }

            $permissions = GroupPermission::where('gorup', $user->group_id)->get();
            $result = [];

            foreach ($permissions as $permission) {
                // Initialize sub_module array if it doesn't exist
                if (!isset($result[$permission->sub_module])) {
                    $result[$permission->sub_module] = [];
                }
          
                // If actions are comma-separated in one row
                if (strpos($permission->action, ',') !== false) {
                    $result[$permission->sub_module] = array_merge(
                        $result[$permission->sub_module],
                        $permission->getActionsArray()
                    );
                } else {
                    // If each action is in a separate row
                    $result[$permission->sub_module][] = trim($permission->action);
                }
            }

            // Remove duplicates and reindex arrays
            foreach ($result as $subModule => $actions) {
                $result[$subModule] = array_values(array_unique($actions));
            }

            return $result;
        });
    }

    /**
     * Cache user permissions in session.
     *
     * @param User $user
     * @return void
     */
    public function cacheUserPermissionsInSession(User $user): void
    {
        $permissions = $this->getAllUserPermissions($user);
        Session::put(self::SESSION_KEY, $permissions);
    }

    /**
     * Get user permissions from session.
     *
     * @return array
     */
    public function getPermissionsFromSession(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * Check permission from session data.
     *
     * @param string $subModule
     * @param string $action
     * @return bool
     */
    public function hasPermissionFromSession(string $subModule, string $action): bool
    {
        $permissions = $this->getPermissionsFromSession();
        
        if (!isset($permissions[$subModule])) {
            return false;
        }

        return in_array($action, $permissions[$subModule]);
    }

    /**
     * Clear user permissions cache.
     *
     * @param User $user
     * @return void
     */
    public function clearUserPermissionsCache(User $user): void
    {
        $cacheKeys = [
            self::CACHE_PREFIX . $user->id . '_all',
            self::CACHE_PREFIX . $user->id . '_quality_evaluations',
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        Session::forget(self::SESSION_KEY);
    }

    /**
     * Get Quality Evaluation specific permissions for a user.
     *
     * @param User $user
     * @return array
     */
    public function getQualityEvaluationPermissions(User $user): array
    {
        return $this->getUserPermissions($user, 'quality_evaluations');
    }

    /**
     * Check if user can create Quality Evaluations.
     *
     * @param User $user
     * @return bool
     */
    public function canCreateQualityEvaluation(User $user): bool
    {
        return $this->hasPermission($user, 'quality_evaluations', 'create');
    }

    /**
     * Check if user can view Quality Evaluations.
     *
     * @param User $user
     * @return bool
     */
    public function canViewQualityEvaluation(User $user): bool
    {
        return $this->hasPermission($user, 'quality_evaluations', 'view');
    }

    /**
     * Check if user can edit Quality Evaluations.
     *
     * @param User $user
     * @return bool
     */
    public function canEditQualityEvaluation(User $user): bool
    {
        return $this->hasPermission($user, 'quality_evaluations', 'edit');
    }

    /**
     * Check if user can delete Quality Evaluations.
     *
     * @param User $user
     * @return bool
     */
    public function canDeleteQualityEvaluation(User $user): bool
    {
        return $this->hasPermission($user, 'quality_evaluations', 'delete');
    }
}
