# 🚀 Quality Evaluation Permissions - Admin Setup Guide

## Quick Start for Administrators

This guide helps administrators quickly set up user permissions and data access for the Quality Evaluation system.

## 📋 Prerequisites

Ensure these tables exist in your database:
- `sma_groups` - User groups
- `sma_groups_permissions` - Group permissions
- `sma_users` - Users with group_id
- `users_access` - User data access restrictions

## 🎯 Common Setup Scenarios

### Scenario 1: Quality Manager (Full Access)

**Step 1: Create/Verify Group**
```sql
INSERT INTO sma_groups (id, name, description) VALUES 
(1, 'Quality Managers', 'Full access to quality evaluations');
```

**Step 2: Grant All Permissions**
```sql
INSERT INTO sma_groups_permissions (group_id, sub_module, action) VALUES 
(1, 'quality_evaluations', 'create,view,edit,delete');
```

**Step 3: Grant Unrestricted Data Access**
```sql
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(USER_ID, NULL, NULL, NULL);
```

**Step 4: Assign User to Group**
```sql
UPDATE sma_users SET group_id = 1 WHERE id = USER_ID;
```

### Scenario 2: Branch Inspector (Limited to Specific Branches)

**Step 1: Create Group**
```sql
INSERT INTO sma_groups (id, name, description) VALUES 
(2, 'Branch Inspectors', 'Create and edit evaluations for assigned branches');
```

**Step 2: Grant Create/View/Edit Permissions**
```sql
INSERT INTO sma_groups_permissions (group_id, sub_module, action) VALUES 
(2, 'quality_evaluations', 'create'),
(2, 'quality_evaluations', 'view'),
(2, 'quality_evaluations', 'edit');
```

**Step 3: Restrict to Specific Branches**
```sql
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(USER_ID, 5, 3, 101),  -- Branch 101
(USER_ID, 5, 3, 102),  -- Branch 102
(USER_ID, 5, 3, 103);  -- Branch 103
```

**Step 4: Assign User to Group**
```sql
UPDATE sma_users SET group_id = 2 WHERE id = USER_ID;
```

### Scenario 3: Regional Viewer (View-Only, Brand Level)

**Step 1: Create Group**
```sql
INSERT INTO sma_groups (id, name, description) VALUES 
(3, 'Regional Viewers', 'View-only access to quality evaluations');
```

**Step 2: Grant View Permission Only**
```sql
INSERT INTO sma_groups_permissions (group_id, sub_module, action) VALUES 
(3, 'quality_evaluations', 'view');
```

**Step 3: Grant Brand-Level Access**
```sql
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(USER_ID, 5, 3, NULL);  -- All branches in brand 3
```

**Step 4: Assign User to Group**
```sql
UPDATE sma_users SET group_id = 3 WHERE id = USER_ID;
```

## 🔧 Permission Management

### Adding New Permission
```sql
INSERT INTO sma_groups_permissions (group_id, sub_module, action) VALUES 
(GROUP_ID, 'quality_evaluations', 'ACTION_NAME');
```

### Removing Permission
```sql
DELETE FROM sma_groups_permissions 
WHERE group_id = GROUP_ID 
AND sub_module = 'quality_evaluations' 
AND action = 'ACTION_NAME';
```

### Updating Comma-Separated Permissions
```sql
UPDATE sma_groups_permissions 
SET action = 'create,view,edit' 
WHERE group_id = GROUP_ID 
AND sub_module = 'quality_evaluations';
```

## 🏢 Data Access Management

### Grant Full Access
```sql
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(USER_ID, NULL, NULL, NULL);
```

### Restrict to Country
```sql
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(USER_ID, COUNTRY_ID, NULL, NULL);
```

### Restrict to Brand
```sql
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(USER_ID, COUNTRY_ID, BRAND_ID, NULL);
```

### Restrict to Specific Branches
```sql
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(USER_ID, COUNTRY_ID, BRAND_ID, BRANCH_ID_1),
(USER_ID, COUNTRY_ID, BRAND_ID, BRANCH_ID_2);
```

### Remove All Access Restrictions
```sql
DELETE FROM users_access WHERE user_id = USER_ID;
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(USER_ID, NULL, NULL, NULL);
```

## 📊 Verification Queries

### Check User's Current Permissions
```sql
SELECT 
    u.username,
    g.name as group_name,
    gp.sub_module,
    gp.action
FROM sma_users u
JOIN sma_groups g ON u.group_id = g.id
JOIN sma_groups_permissions gp ON g.id = gp.group_id
WHERE u.id = USER_ID
AND gp.sub_module = 'quality_evaluations';
```

### Check User's Data Access
```sql
SELECT 
    u.username,
    ua.country_id,
    ua.brand_id,
    ua.branch_id,
    CASE 
        WHEN ua.branch_id IS NULL THEN 'Unrestricted'
        ELSE 'Restricted'
    END as access_level
FROM sma_users u
LEFT JOIN users_access ua ON u.id = ua.user_id
WHERE u.id = USER_ID;
```

### List All Users with Quality Permissions
```sql
SELECT 
    u.id,
    u.username,
    u.first_name,
    u.last_name,
    g.name as group_name,
    GROUP_CONCAT(gp.action) as permissions
FROM sma_users u
JOIN sma_groups g ON u.group_id = g.id
JOIN sma_groups_permissions gp ON g.id = gp.group_id
WHERE gp.sub_module = 'quality_evaluations'
GROUP BY u.id, g.name;
```

## ⚠️ Important Notes

### Permission Caching
- Permissions are cached in user sessions
- Users need to log out and log back in after permission changes
- Or clear cache programmatically:
```php
$permissionService->clearUserPermissionsCache($user);
$dataAccessService->clearUserDataAccessCache($user);
```

### Data Access Rules
- `NULL` or `0` values = Unrestricted access
- Specific IDs = Restricted to those entities only
- No records in `users_access` = Full access (default)

### Security Best Practices
1. **Principle of Least Privilege** - Grant minimum required permissions
2. **Regular Audits** - Review user permissions periodically
3. **Group-Based Management** - Use groups instead of individual permissions
4. **Data Segregation** - Implement proper branch/location restrictions

## 🆘 Emergency Access

### Grant Emergency Full Access
```sql
-- Backup current permissions
CREATE TABLE temp_user_backup AS 
SELECT * FROM users_access WHERE user_id = USER_ID;

-- Grant full access
DELETE FROM users_access WHERE user_id = USER_ID;
INSERT INTO users_access (user_id, country_id, brand_id, branch_id) VALUES 
(USER_ID, NULL, NULL, NULL);

-- Update to admin group (if exists)
UPDATE sma_users SET group_id = 1 WHERE id = USER_ID;
```

### Restore from Backup
```sql
-- Remove emergency access
DELETE FROM users_access WHERE user_id = USER_ID;

-- Restore from backup
INSERT INTO users_access SELECT * FROM temp_user_backup;

-- Drop backup table
DROP TABLE temp_user_backup;
```

## 📞 Support

For assistance with permission setup:
1. Verify database table structure
2. Check user group assignments
3. Review permission and access configurations
4. Test with a non-admin user account
5. Contact development team if issues persist

---

**Remember:** Always test permission changes with a non-admin user account to verify the configuration works as expected!
