<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAccess;
use App\Models\Branch;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DataAccessService
{
    const CACHE_PREFIX = 'user_access_';
    const CACHE_TTL = 3600; // 1 hour
    const SESSION_KEY = 'user_data_access';

    /**
     * Get user's data access restrictions.
     *
     * @param User $user
     * @return array
     */
    public function getUserDataAccess(User $user): array
    {
        $cacheKey = self::CACHE_PREFIX . $user->id;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $accessRecords = UserAccess::getUserAccess($user->id);

            if ($accessRecords->isEmpty()) {
                return [
                    'unrestricted' => true,
                    'branch_ids' => null,
                    'country_ids' => null,
                    'brand_ids' => null,
                ];
            }

            // Check for completely unrestricted access (all fields null/0)
            $hasCompletelyUnrestrictedAccess = $accessRecords->contains(function ($access) {
                return (is_null($access->country_id) || $access->country_id === 0) &&
                       (is_null($access->brand_id) || $access->brand_id === 0) &&
                       (is_null($access->branch_id) || $access->branch_id === 0);
            });

            if ($hasCompletelyUnrestrictedAccess) {
                return [
                    'unrestricted' => true,
                    'branch_ids' => null,
                    'country_ids' => null,
                    'brand_ids' => null,
                ];
            }

            // Get specific IDs from records with explicit values
            $explicitBranchIds = $accessRecords
                ->whereNotNull('branch_id')
                ->where('branch_id', '>', 0)
                ->pluck('branch_id')
                ->unique()
                ->values()
                ->toArray();

            $explicitCountryIds = $accessRecords
                ->whereNotNull('country_id')
                ->where('country_id', '>', 0)
                ->pluck('country_id')
                ->unique()
                ->values()
                ->toArray();

            $explicitBrandIds = $accessRecords
                ->whereNotNull('brand_id')
                ->where('brand_id', '>', 0)
                ->pluck('brand_id')
                ->unique()
                ->values()
                ->toArray();

            // Calculate effective branch access based on hierarchical permissions
            $effectiveBranchIds = $this->calculateEffectiveBranchIds($accessRecords, $explicitBranchIds);

            return [
                'unrestricted' => false,
                'branch_ids' => $effectiveBranchIds,
                'country_ids' => empty($explicitCountryIds) ? null : $explicitCountryIds,
                'brand_ids' => empty($explicitBrandIds) ? null : $explicitBrandIds,
            ];
        });
    }

    /**
     * Calculate effective branch IDs based on hierarchical permissions.
     *
     * @param Collection $accessRecords
     * @param array $explicitBranchIds
     * @return array|null
     */
    private function calculateEffectiveBranchIds($accessRecords, array $explicitBranchIds): ?array
    {
        $allAllowedBranchIds = [];

        // Add explicitly allowed branch IDs
        $allAllowedBranchIds = array_merge($allAllowedBranchIds, $explicitBranchIds);

        // For each access record, determine what branches it grants access to
        foreach ($accessRecords as $access) {
            $countryId = $access->country_id;
            $brandId = $access->brand_id;
            $branchId = $access->branch_id;

            // If branch_id is specified, it's already in explicitBranchIds
            if (!is_null($branchId) && $branchId > 0) {
                continue;
            }

            // If branch_id is null but brand_id is specified, get all branches in that brand
            if ((is_null($branchId) || $branchId === 0) && !is_null($brandId) && $brandId > 0) {
                $branchQuery = Branch::where('brand_id', $brandId);

                // Also filter by country if specified
                if (!is_null($countryId) && $countryId > 0) {
                    $branchQuery->where('country_id', $countryId);
                }

                $brandBranchIds = $branchQuery->pluck('id')->toArray();
                $allAllowedBranchIds = array_merge($allAllowedBranchIds, $brandBranchIds);
            }

            // If both branch_id and brand_id are null but country_id is specified, get all branches in that country
            if ((is_null($branchId) || $branchId === 0) &&
                (is_null($brandId) || $brandId === 0) &&
                !is_null($countryId) && $countryId > 0) {
                $countryBranchIds = Branch::where('country_id', $countryId)->pluck('id')->toArray();
                $allAllowedBranchIds = array_merge($allAllowedBranchIds, $countryBranchIds);
            }
        }

        // Remove duplicates and return
        $allAllowedBranchIds = array_unique($allAllowedBranchIds);
        return empty($allAllowedBranchIds) ? null : array_values($allAllowedBranchIds);
    }

    /**
     * Cache user data access in session.
     *
     * @param User $user
     * @return void
     */
    public function cacheUserDataAccessInSession(User $user): void
    {
        $dataAccess = $this->getUserDataAccess($user);
        Session::put(self::SESSION_KEY, $dataAccess);
    }

    /**
     * Get user data access from session.
     *
     * @return array
     */
    public function getDataAccessFromSession(): array
    {
        return Session::get(self::SESSION_KEY, [
            'unrestricted' => true,
            'branch_ids' => null,
            'country_ids' => null,
            'brand_ids' => null,
        ]);
    }

    /**
     * Get filtered branches for a user.
     *
     * @param User $user
     * @return Collection
     */
    public function getFilteredBranches(User $user): Collection
    {
        $dataAccess = $this->getUserDataAccess($user);

        if ($dataAccess['unrestricted']) {
            return Branch::all();
        }

        if (is_null($dataAccess['branch_ids'])) {
            return collect(); // No access to any branches
        }

        return Branch::whereIn('id', $dataAccess['branch_ids'])->get();
    }

    /**
     * Get filtered branches from session data.
     *
     * @return Collection
     */
    public function getFilteredBranchesFromSession(): Collection
    {
        $dataAccess = $this->getDataAccessFromSession();

        if ($dataAccess['unrestricted']) {
            return Branch::all();
        }

        if (is_null($dataAccess['branch_ids'])) {
            return collect(); // No access to any branches
        }

        return Branch::whereIn('id', $dataAccess['branch_ids'])->get();
    }

    /**
     * Apply branch filtering to a query builder.
     *
     * @param Builder $query
     * @param User $user
     * @param string $branchColumn
     * @return Builder
     */
    public function applyBranchFilter(Builder $query, User $user, string $branchColumn = 'branch_id'): Builder
    {
        $dataAccess = $this->getUserDataAccess($user);

        if (!$dataAccess['unrestricted'] && !is_null($dataAccess['branch_ids'])) {
            $query->whereIn($branchColumn, $dataAccess['branch_ids']);
        } elseif (!$dataAccess['unrestricted'] && is_null($dataAccess['branch_ids'])) {
            // No access to any branches - return empty result
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Apply branch filtering to a query builder using session data.
     *
     * @param Builder $query
     * @param string $branchColumn
     * @return Builder
     */
    public function applyBranchFilterFromSession(Builder $query, string $branchColumn = 'branch_id'): Builder
    {
        $dataAccess = $this->getDataAccessFromSession();

        if (!$dataAccess['unrestricted'] && !is_null($dataAccess['branch_ids'])) {
            $query->whereIn($branchColumn, $dataAccess['branch_ids']);
        } elseif (!$dataAccess['unrestricted'] && is_null($dataAccess['branch_ids'])) {
            // No access to any branches - return empty result
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Check if user has access to a specific branch.
     *
     * @param User $user
     * @param int $branchId
     * @return bool
     */
    public function hasAccessToBranch(User $user, int $branchId): bool
    {
        $dataAccess = $this->getUserDataAccess($user);

        if ($dataAccess['unrestricted']) {
            return true;
        }

        if (is_null($dataAccess['branch_ids'])) {
            return false; // No access to any branches
        }

        return in_array($branchId, $dataAccess['branch_ids']);
    }

    /**
     * Check if user has access to a specific branch using session data.
     *
     * @param int $branchId
     * @return bool
     */
    public function hasAccessToBranchFromSession(int $branchId): bool
    {
        $dataAccess = $this->getDataAccessFromSession();

        if ($dataAccess['unrestricted']) {
            return true;
        }

        if (is_null($dataAccess['branch_ids'])) {
            return false; // No access to any branches
        }

        return in_array($branchId, $dataAccess['branch_ids']);
    }

    /**
     * Clear user data access cache.
     *
     * @param User $user
     * @return void
     */
    public function clearUserDataAccessCache(User $user): void
    {
        $cacheKey = self::CACHE_PREFIX . $user->id;
        Cache::forget($cacheKey);
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Get allowed branch IDs for a user.
     *
     * @param User $user
     * @return array|null Returns null for unrestricted access
     */
    public function getAllowedBranchIds(User $user): ?array
    {
        $dataAccess = $this->getUserDataAccess($user);
        return $dataAccess['branch_ids'];
    }

    /**
     * Get allowed branch IDs from session.
     *
     * @return array|null Returns null for unrestricted access
     */
    public function getAllowedBranchIdsFromSession(): ?array
    {
        $dataAccess = $this->getDataAccessFromSession();
        return $dataAccess['branch_ids'];
    }
}
