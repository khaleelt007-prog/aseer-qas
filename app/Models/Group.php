<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    public $timestamps = false;
    protected $table = "sma_groups";

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the users in this group.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the permissions for this group.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(GroupPermission::class);
    }

    /**
     * Get a specific permission for this group and sub-module.
     *
     * @param string $subModule
     * @return GroupPermission|null
     */
    public function getPermission(string $subModule): ?GroupPermission
    {
        return $this->permissions()
                   ->where('sub_module', $subModule)
                   ->first();
    }

    /**
     * Check if this group has a specific permission.
     *
     * @param string $subModule
     * @param string $action
     * @return bool
     */
    public function hasPermission(string $subModule, string $action): bool
    {
        $permission = $this->getPermission($subModule);
        
        if (!$permission) {
            return false;
        }

        return $permission->hasAction($action);
    }
}
