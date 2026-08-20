# 🔐 Quality Evaluation Permission System Documentation

## Table of Contents
1. [Overview](#overview)
2. [Database Schema](#database-schema)
3. [Permission Types](#permission-types)
4. [Data Access Control](#data-access-control)
5. [Implementation Architecture](#implementation-architecture)
6. [Configuration Guide](#configuration-guide)
7. [User Interface Behavior](#user-interface-behavior)
8. [API Reference](#api-reference)
9. [Troubleshooting](#troubleshooting)

## Overview

The Quality Evaluation Permission System provides comprehensive role-based access control for the Quality Control Evaluation feature. It implements a multi-layered security approach with granular permissions and data access restrictions.

### Key Features
- **Role-based permissions** using group-based authorization
- **Data access control** with branch/location filtering
- **Flexible permission storage** (comma-separated or individual rows)
- **Session-based caching** for optimal performance
- **Multi-layered security** (route, controller, and UI levels)
- **Graceful UI degradation** based on user permissions

### Supported Actions
- `create` - Create new quality evaluations
- `view` - View existing quality evaluations
- `edit` - Modify existing quality evaluations
- `delete` - Remove quality evaluations

## Database Schema

### sma_groups_permissions Table
Stores permission definitions for user groups.

```sql
CREATE TABLE sma_groups_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    sub_module VARCHAR(255) NOT NULL,
    action TEXT NOT NULL,
    INDEX idx_group_module (group_id, sub_module)
);
```

**Columns:**
- `group_id` - References the user's group
- `sub_module` - Module identifier (e.g., 'quality_evaluations')
- `action` - Permitted actions (comma-separated or individual)

### users_access Table
Defines data access restrictions for users.

```sql
CREATE TABLE users_access (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    country_id INT NULL,
    brand_id INT NULL,
    branch_id INT NULL,
    INDEX idx_user_access (user_id)
);
```

**Access Rules:**
- `NULL` or `0` values = Unrestricted access
- Specific IDs = Restricted to those entities only

## Permission Types

### 1. Comma-Separated Permissions
Store multiple actions in a single row:

```sql
INSERT INTO sma_groups_permissions (group_id, sub_module, action) VALUES 
(1, 'quality_evaluations', 'create,view,edit,delete');
```

### 2. Individual Row Permissions
Store each action in separate rows:

```sql
INSERT INTO sma_groups_permissions (group_id, sub_module, action) VALUES 
(1, 'quality_evaluations', 'create'),
(1, 'quality_evaluations', 'view'),
(1, 'quality_evaluations', 'edit'),
(1, 'quality_evaluations', 'delete');
```

## Data Access Control

### Unrestricted Access
User has access to all branches:

```sql
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(1, NULL, NULL, NULL);
-- OR
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(1, 0, 0, 0);
```

### Restricted Access
User limited to specific branches:

```sql
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(2, 5, 3, 101),
(2, 5, 3, 102),
(2, 5, 3, 103);
```

### Mixed Access Levels
```sql
-- Full country access
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(3, 5, NULL, NULL);

-- Specific brand access
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(4, 5, 3, NULL);
```

## Implementation Architecture

### Backend Components

#### 1. Models
- `GroupPermission` - Permission management
- `UserAccess` - Data access control
- `User` - Extended with permission helper methods

#### 2. Services
- `PermissionService` - Permission checking and caching
- `DataAccessService` - Data filtering and access control

#### 3. Middleware
- `CheckQualityEvaluationPermission` - Route-level protection

#### 4. Policies
- `QualityEvaluationPolicy` - Centralized authorization logic

### Frontend Components
- Permission-aware Vue.js components
- Dynamic UI element visibility
- Graceful permission handling

## Configuration Guide

### 1. Setting Up User Groups

```sql
-- Create user groups
INSERT INTO sma_groups (id, name, description) VALUES 
(1, 'Quality Managers', 'Full quality evaluation access'),
(2, 'Quality Inspectors', 'Create and edit evaluations'),
(3, 'Quality Viewers', 'View-only access');
```

### 2. Assigning Permissions

**Full Access Group:**
```sql
INSERT INTO sma_groups_permissions (group_id, sub_module, action) VALUES 
(1, 'quality_evaluations', 'create,view,edit,delete');
```

**Inspector Group:**
```sql
INSERT INTO sma_groups_permissions (group_id, sub_module, action) VALUES 
(2, 'quality_evaluations', 'create'),
(2, 'quality_evaluations', 'view'),
(2, 'quality_evaluations', 'edit');
```

**Viewer Group:**
```sql
INSERT INTO sma_groups_permissions (group_id, sub_module, action) VALUES 
(3, 'quality_evaluations', 'view');
```

### 3. Configuring Data Access

**Branch Managers (specific branches):**
```sql
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(101, 5, 3, 201),
(101, 5, 3, 202);
```

**Regional Managers (brand-wide access):**
```sql
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(102, 5, 3, NULL);
```

**Country Managers (country-wide access):**
```sql
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(103, 5, NULL, NULL);
```

### 4. User Assignment

```sql
-- Assign users to groups
UPDATE sma_users SET group_id = 1 WHERE id = 101; -- Quality Manager
UPDATE sma_users SET group_id = 2 WHERE id = 102; -- Quality Inspector  
UPDATE sma_users SET group_id = 3 WHERE id = 103; -- Quality Viewer
```

## User Interface Behavior

### Index Page (List View)

**Full Permissions:**
- ✅ Create button visible
- ✅ View/Edit/Delete buttons for each evaluation
- ✅ All evaluations within user's data access scope

**Limited Permissions:**
- ❌ Create button hidden (no create permission)
- ✅ View button only (view-only permission)
- ⚠️ "No actions available" message for restricted items

**No Permissions:**
- ❌ All action buttons hidden
- ℹ️ "No actions available based on your permissions" message

### Show Page (Detail View)

**Edit Permission:**
- ✅ Edit button in header
- ✅ Edit button in action section

**Delete Permission:**
- ✅ Delete button in action section
- ✅ Delete confirmation modal

**No Permissions:**
- ❌ All action buttons hidden
- ℹ️ "No actions available" message displayed

### Create Page (Form)

**Branch Access:**
- ✅ Dropdown shows only accessible branches
- ⚠️ Warning if no branches available
- ❌ Form disabled if no branch access

**No Create Permission:**
- 🚫 Route blocked by middleware
- 🚫 403 Forbidden error

## API Reference

### PermissionService Methods

```php
// Check specific permission
$permissionService->hasPermission($user, 'quality_evaluations', 'create');

// Get all permissions for sub-module
$permissions = $permissionService->getUserPermissions($user, 'quality_evaluations');

// Cache permissions in session
$permissionService->cacheUserPermissionsInSession($user);

// Clear permission cache
$permissionService->clearUserPermissionsCache($user);
```

### DataAccessService Methods

```php
// Get filtered branches
$branches = $dataAccessService->getFilteredBranches($user);

// Check branch access
$hasAccess = $dataAccessService->hasAccessToBranch($user, $branchId);

// Apply query filtering
$query = $dataAccessService->applyBranchFilter($query, $user);

// Get allowed branch IDs
$branchIds = $dataAccessService->getAllowedBranchIds($user);
```

### User Model Helper Methods

```php
// Permission checks
$user->hasPermission('quality_evaluations', 'create');
$user->canCreateQualityEvaluation();
$user->canEditQualityEvaluation();
$user->canDeleteQualityEvaluation();

// Data access checks
$user->hasAccessToBranch($branchId);
$user->getAllowedBranchIds();
$user->hasUnrestrictedBranchAccess();
```

## Troubleshooting

### Common Issues

#### 1. User Can't See Any Evaluations
**Cause:** No data access configured
**Solution:** 
```sql
-- Grant unrestricted access
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(USER_ID, NULL, NULL, NULL);
```

#### 2. Buttons Not Showing
**Cause:** Missing permissions
**Solution:**
```sql
-- Check user's group permissions
SELECT * FROM sma_groups_permissions 
WHERE group_id = (SELECT group_id FROM sma_users WHERE id = USER_ID)
AND sub_module = 'quality_evaluations';
```

#### 3. 403 Forbidden Errors
**Cause:** Route middleware blocking access
**Solution:** Verify user has required permission for the action

#### 4. Branch Dropdown Empty
**Cause:** No branch access configured
**Solution:**
```sql
-- Check user's data access
SELECT * FROM users_access WHERE user_id = USER_ID;
```

### Debug Commands

```php
// Check user permissions
$user = User::find(1);
dd($user->getQualityEvaluationPermissions());

// Check data access
dd($user->getAllowedBranchIds());

// Verify session cache
dd(session('user_permissions'));
dd(session('user_data_access'));
```

### Performance Monitoring

```php
// Clear caches if needed
$permissionService->clearUserPermissionsCache($user);
$dataAccessService->clearUserDataAccessCache($user);

// Re-cache on next login
$permissionService->cacheUserPermissionsInSession($user);
$dataAccessService->cacheUserDataAccessInSession($user);
```

## Developer Integration Guide

### Adding Permission Checks to New Features

#### 1. Controller Integration
```php
class NewFeatureController extends Controller
{
    protected $permissionService;
    protected $dataAccessService;

    public function __construct(PermissionService $permissionService, DataAccessService $dataAccessService)
    {
        $this->permissionService = $permissionService;
        $this->dataAccessService = $dataAccessService;
    }

    public function index()
    {
        // Apply data filtering
        $query = NewFeature::query();
        $this->dataAccessService->applyBranchFilterFromSession($query);

        return $query->paginate(10);
    }
}
```

#### 2. Middleware Application
```php
// In routes/web.php
Route::middleware(['auth', 'quality.permission:view'])->group(function () {
    Route::get('/new-feature', [NewFeatureController::class, 'index']);
});
```

#### 3. Vue.js Component Integration
```vue
<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const permissions = computed(() => page.props.auth.permissions.quality_evaluations || [])

const canCreate = computed(() => permissions.value.includes('create'))
const canEdit = computed(() => permissions.value.includes('edit'))
</script>

<template>
    <button v-if="canCreate" @click="create">Create</button>
    <button v-if="canEdit" @click="edit">Edit</button>
</template>
```

### Extending to Other Modules

#### 1. Add New Sub-Module Permissions
```sql
INSERT INTO sma_groups_permissions (group_id, sub_module, action) VALUES
(1, 'inventory_management', 'create,view,edit,delete'),
(1, 'staff_management', 'view,edit');
```

#### 2. Create Module-Specific Service Methods
```php
// In PermissionService.php
public function getInventoryPermissions(User $user): array
{
    return $this->getUserPermissions($user, 'inventory_management');
}

public function canManageInventory(User $user): bool
{
    return $this->hasPermission($user, 'inventory_management', 'edit');
}
```

#### 3. Add Module-Specific Middleware
```php
class CheckInventoryPermission extends CheckQualityEvaluationPermission
{
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $user = Auth::user();

        if (!$this->permissionService->hasPermission($user, 'inventory_management', $action)) {
            abort(403, 'Insufficient inventory permissions.');
        }

        return $next($request);
    }
}
```

---

## Support

For technical support or questions about the permission system:
1. Check the troubleshooting section above
2. Verify database configuration
3. Review application logs for permission-related errors
4. Contact the development team with specific error details

## Related Documentation
- [Admin Setup Guide](./ADMIN_SETUP_GUIDE.md) - Quick setup for administrators
- [API Reference](./API_REFERENCE.md) - Detailed API documentation
- [Security Best Practices](./SECURITY_GUIDE.md) - Security implementation guidelines
