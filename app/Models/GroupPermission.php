<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupPermission extends Model
{
    public $timestamps = false;
    protected $table = "sma_groups_permissions";

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'group',
        'sub_module',
        'action',
    ];

    /**
     * Get the group that owns this permission.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Parse the comma-separated actions into an array.
     *
     * @return array
     */
    public function getActionsArray(): array
    {
        if (empty($this->action)) {
            return [];
        }

        return array_map('trim', explode(',', $this->action));
    }

    /**
     * Check if the permission includes a specific action.
     *
     * @param string $action
     * @return bool
     */
    public function hasAction(string $action): bool
    {
        return in_array($action, $this->getActionsArray());
    }

    /**
     * Get permissions for a specific group and sub-module.
     * Returns all permission records (may be multiple rows).
     *
     * @param int $groupId
     * @param string $subModule
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPermissions(int $groupId, string $subModule)
    {
        return static::where('gorup', $groupId)
                    ->where('sub_module', $subModule)
                    ->get();
    }

    /**
     * Get first permission for a specific group and sub-module.
     * (For backward compatibility)
     *
     * @param int $groupId
     * @param string $subModule
     * @return GroupPermission|null
     */
    public static function getPermission(int $groupId, string $subModule): ?GroupPermission
    {
        return static::where('group_id', $groupId)
                    ->where('sub_module', $subModule)
                    ->first();
    }

    /**
     * Check if a group has a specific permission for a sub-module.
     *
     * @param int $groupId
     * @param string $subModule
     * @param string $action
     * @return bool
     */
    public static function hasPermission(int $groupId, string $subModule, string $action): bool
    {
        $permissions = static::getPermissions($groupId, $subModule);

        if ($permissions->isEmpty()) {
            return false;
        }

        foreach ($permissions as $permission) {
            // Check if actions are comma-separated in one row
            if (strpos($permission->action, ',') !== false) {
                if ($permission->hasAction($action)) {
                    return true;
                }
            } else {
                // Check if this row contains the specific action
                if (trim($permission->action) === $action) {
                    return true;
                }
            }
        }

        return false;
    }
}
