# 📚 Quality Evaluation Permissions - API Reference

## Service Classes

### PermissionService

Handles permission checking and caching for user groups.

#### Methods

##### `hasPermission(User $user, string $subModule, string $action): bool`
Check if user has a specific permission.

**Parameters:**
- `$user` - User instance
- `$subModule` - Module name (e.g., 'quality_evaluations')
- `$action` - Action name ('create', 'view', 'edit', 'delete')

**Returns:** `bool` - True if user has permission

**Example:**
```php
$canEdit = $permissionService->hasPermission($user, 'quality_evaluations', 'edit');
```

##### `getUserPermissions(User $user, string $subModule): array`
Get all permissions for a user in a specific module.

**Returns:** `array` - List of permitted actions

**Example:**
```php
$permissions = $permissionService->getUserPermissions($user, 'quality_evaluations');
// Returns: ['create', 'view', 'edit']
```

##### `getAllUserPermissions(User $user): array`
Get all permissions for a user across all modules.

**Returns:** `array` - Associative array of module => actions

**Example:**
```php
$allPermissions = $permissionService->getAllUserPermissions($user);
// Returns: ['quality_evaluations' => ['create', 'view'], 'inventory' => ['view']]
```

##### `cacheUserPermissionsInSession(User $user): void`
Cache user permissions in session for performance.

##### `clearUserPermissionsCache(User $user): void`
Clear cached permissions for a user.

#### Quality Evaluation Specific Methods

##### `canCreateQualityEvaluation(User $user): bool`
##### `canViewQualityEvaluation(User $user): bool`
##### `canEditQualityEvaluation(User $user): bool`
##### `canDeleteQualityEvaluation(User $user): bool`

Convenience methods for Quality Evaluation permissions.

---

### DataAccessService

Manages data access restrictions and filtering.

#### Methods

##### `hasAccessToBranch(User $user, int $branchId): bool`
Check if user has access to a specific branch.

**Example:**
```php
$hasAccess = $dataAccessService->hasAccessToBranch($user, 101);
```

##### `getFilteredBranches(User $user): Collection`
Get branches accessible to the user.

**Returns:** `Collection` - Filtered branch collection

##### `applyBranchFilter(Builder $query, User $user, string $branchColumn = 'branch_id'): Builder`
Apply branch filtering to a query builder.

**Example:**
```php
$query = QualityEvaluation::query();
$filteredQuery = $dataAccessService->applyBranchFilter($query, $user);
```

##### `getAllowedBranchIds(User $user): ?array`
Get array of allowed branch IDs for user.

**Returns:** `array|null` - Array of IDs or null for unrestricted access

##### `getUserDataAccess(User $user): array`
Get complete data access configuration for user.

**Returns:**
```php
[
    'unrestricted' => bool,
    'branch_ids' => array|null,
    'country_ids' => array|null,
    'brand_ids' => array|null
]
```

---

## Model Methods

### User Model Extensions

#### Permission Methods
```php
$user->hasPermission('quality_evaluations', 'create');
$user->getPermissions('quality_evaluations');
$user->getQualityEvaluationPermissions();
$user->canCreateQualityEvaluation();
$user->canViewQualityEvaluation();
$user->canEditQualityEvaluation();
$user->canDeleteQualityEvaluation();
```

#### Data Access Methods
```php
$user->hasAccessToBranch($branchId);
$user->getAllowedBranchIds();
$user->hasUnrestrictedBranchAccess();
```

### GroupPermission Model

#### Static Methods
```php
GroupPermission::hasPermission($groupId, $subModule, $action);
GroupPermission::getPermission($groupId, $subModule);
GroupPermission::getPermissions($groupId, $subModule);
```

#### Instance Methods
```php
$permission->getActionsArray();
$permission->hasAction($action);
```

### UserAccess Model

#### Static Methods
```php
UserAccess::getUserAccess($userId);
UserAccess::hasUnrestrictedBranchAccess($userId);
UserAccess::getAllowedBranchIds($userId);
UserAccess::hasAccessToBranch($userId, $branchId);
```

---

## Middleware

### CheckQualityEvaluationPermission

Route-level permission enforcement middleware.

#### Usage
```php
Route::middleware('quality.permission:create')->group(function () {
    Route::post('/quality-evaluations', [QualityEvaluationController::class, 'store']);
});
```

#### Parameters
- `create` - Requires create permission
- `view` - Requires view permission
- `edit` - Requires edit permission
- `delete` - Requires delete permission

---

## Policy Methods

### QualityEvaluationPolicy

#### Authorization Methods
```php
$user->can('viewAny', QualityEvaluation::class);
$user->can('view', $qualityEvaluation);
$user->can('create', QualityEvaluation::class);
$user->can('update', $qualityEvaluation);
$user->can('delete', $qualityEvaluation);
$user->can('managePhotos', $qualityEvaluation);
$user->can('accessBranch', QualityEvaluation::class, $branchId);
```

---

## Frontend Integration

### Inertia Props

Permissions and data access are automatically passed to Vue components:

```javascript
// In Vue components
const page = usePage()
const auth = computed(() => page.props.auth)
const permissions = computed(() => auth.value.permissions.quality_evaluations || [])
const dataAccess = computed(() => auth.value.dataAccess)

// Check permissions
const canCreate = computed(() => permissions.value.includes('create'))
const canEdit = computed(() => permissions.value.includes('edit'))

// Check data access
const allowedBranches = computed(() => dataAccess.value.branch_ids)
const hasUnrestrictedAccess = computed(() => dataAccess.value.unrestricted)
```

### Permission Checking in Vue
```vue
<template>
    <div>
        <button v-if="canCreate" @click="create">Create</button>
        <button v-if="canEdit" @click="edit">Edit</button>
        <button v-if="canDelete" @click="remove">Delete</button>
        
        <div v-if="!hasAnyPermissions" class="no-permissions">
            No actions available based on your permissions.
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const permissions = computed(() => page.props.auth.permissions.quality_evaluations || [])

const canCreate = computed(() => permissions.value.includes('create'))
const canEdit = computed(() => permissions.value.includes('edit'))
const canDelete = computed(() => permissions.value.includes('delete'))
const hasAnyPermissions = computed(() => permissions.value.length > 0)
</script>
```

---

## Error Handling

### HTTP Status Codes
- `403 Forbidden` - User lacks required permission
- `404 Not Found` - Resource not accessible due to data restrictions

### Error Responses

#### JSON API Response
```json
{
    "message": "You do not have permission to create quality evaluations.",
    "error": "Insufficient permissions"
}
```

#### Web Response
Redirects to 403 error page with appropriate message.

---

## Caching

### Session Keys
- `user_permissions` - Cached user permissions
- `user_data_access` - Cached data access configuration

### Cache Keys
- `user_permissions_{user_id}_all` - All user permissions
- `user_permissions_{user_id}_{sub_module}` - Module-specific permissions
- `user_access_{user_id}` - User data access configuration

### Cache Management
```php
// Clear all caches for user
$permissionService->clearUserPermissionsCache($user);
$dataAccessService->clearUserDataAccessCache($user);

// Refresh caches
$permissionService->cacheUserPermissionsInSession($user);
$dataAccessService->cacheUserDataAccessInSession($user);
```

---

## Database Queries

### Permission Lookup
```sql
-- Get user permissions
SELECT gp.* FROM sma_groups_permissions gp
JOIN sma_users u ON u.group_id = gp.group_id
WHERE u.id = ? AND gp.sub_module = 'quality_evaluations';

-- Get user data access
SELECT * FROM users_access WHERE user_id = ?;
```

### Performance Optimization
- Permissions cached in session after login
- Database queries minimized through caching
- Efficient indexing on group_id and user_id columns
