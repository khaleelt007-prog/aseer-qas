<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;

class UserAccess extends Model
{
    public $timestamps = false;
    protected $table = "users_access";

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'country_id',
        'brand_id',
        'branch_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'country_id' => 'integer',
            'brand_id' => 'integer',
            'branch_id' => 'integer',
        ];
    }

    /**
     * Get the user that owns this access record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the country for this access record.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the brand for this access record.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the branch for this access record.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get all access records for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public static function getUserAccess(int $userId): Collection
    {
        return static::where('user_id', $userId)->get();
    }

    /**
     * Check if user has access to all branches (unrestricted access).
     *
     * @param int $userId
     * @return bool
     */
    public static function hasUnrestrictedBranchAccess(int $userId): bool
    {
        $accessRecords = static::getUserAccess($userId);
        
        if ($accessRecords->isEmpty()) {
            return true; // No restrictions means full access
        }

        // Check if any record has null or 0 branch_id (unrestricted)
        return $accessRecords->contains(function ($access) {
            return is_null($access->branch_id) || $access->branch_id === 0;
        });
    }

    /**
     * Get allowed branch IDs for a user.
     *
     * @param int $userId
     * @return array|null Returns null for unrestricted access, array of IDs for restricted access
     */
    public static function getAllowedBranchIds(int $userId): ?array
    {
        if (static::hasUnrestrictedBranchAccess($userId)) {
            return null; // Unrestricted access
        }

        $accessRecords = static::getUserAccess($userId);
        
        return $accessRecords
            ->whereNotNull('branch_id')
            ->where('branch_id', '>', 0)
            ->pluck('branch_id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Check if user has access to a specific branch.
     *
     * @param int $userId
     * @param int $branchId
     * @return bool
     */
    public static function hasAccessToBranch(int $userId, int $branchId): bool
    {
        $allowedBranchIds = static::getAllowedBranchIds($userId);
        
        // If null, user has unrestricted access
        if (is_null($allowedBranchIds)) {
            return true;
        }

        // Check if the specific branch is in the allowed list
        return in_array($branchId, $allowedBranchIds);
    }
}
