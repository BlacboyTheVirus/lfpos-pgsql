# RBAC Replication Plan for PostgreSQL Migration

## Executive Summary

This document provides a comprehensive guide to replicate the Role-Based Access Control (RBAC) system from the existing MySQL-based Laravel POS application to a PostgreSQL environment. The RBAC system is built using **Filament Shield v3.9** (which extends **Spatie Laravel Permission**) and is deeply integrated with **Filament v3.3** admin panel.

---

## Table of Contents

1. [RBAC Architecture Overview](#rbac-architecture-overview)
2. [Package Dependencies](#package-dependencies)
3. [Database Schema](#database-schema)
4. [Configuration Files](#configuration-files)
5. [Models & Relationships](#models--relationships)
6. [Policies](#policies)
7. [Super Admin System](#super-admin-system)
8. [Seeding & Initialization](#seeding--initialization)
9. [PostgreSQL Migration Steps](#postgresql-migration-steps)
10. [Testing Checklist](#testing-checklist)
11. [Common Gotchas](#common-gotchas)

---

## RBAC Architecture Overview

### Core Components

The RBAC system consists of multiple layers:

1. **Spatie Laravel Permission** - Base permission/role management
2. **Filament Shield** - Filament-specific permission integration
3. **Laravel Policies** - Authorization logic per resource
4. **Model Observers** - Security enforcement for super admin
5. **Custom Protection Logic** - Prevents super admin manipulation

### Permission Naming Convention

Filament Shield uses a consistent permission naming pattern:

```
{action}_{resource}

Examples:
- view_any_customer
- view_customer
- create_customer
- update_customer
- delete_customer
- delete_any_customer
- restore_customer
- restore_any_customer
- replicate_customer
- reorder_customer
- force_delete_customer
- force_delete_any_customer
```

### Role Structure

The application has 4 roles:

1. **super_admin** - Full system access (protected from modification)
2. **Admin** - High-level management access
3. **Manager** - Mid-level operations access
4. **Staff** - Basic operational access

---

## Package Dependencies

### Required Composer Packages

```json
{
    "bezhansalleh/filament-shield": "^3.9",
    "filament/filament": "^3.3",
    "spatie/laravel-permission": "^6.x"
}
```

### Installation Commands

```bash
# Install Filament Shield
composer require bezhansalleh/filament-shield:"^3.9"

# Publish configurations
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --tag="filament-shield-config"

# Setup Shield
php artisan shield:setup

# Run migrations
php artisan migrate
```

---

## Database Schema

### Required Tables

The RBAC system requires 5 core tables from Spatie Permission:

#### 1. `permissions` Table

```sql
CREATE TABLE permissions (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name)
);

CREATE INDEX permissions_name_index ON permissions(name);
```

#### 2. `roles` Table

```sql
CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    guard_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name)
);

CREATE INDEX roles_name_index ON roles(name);
```

#### 3. `model_has_permissions` Table (Pivot)

```sql
CREATE TABLE model_has_permissions (
    permission_id BIGINT NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id BIGINT NOT NULL,
    PRIMARY KEY (permission_id, model_id, model_type),
    CONSTRAINT model_has_permissions_permission_id_foreign
        FOREIGN KEY (permission_id)
        REFERENCES permissions(id)
        ON DELETE CASCADE
);

CREATE INDEX model_has_permissions_model_id_model_type_index
    ON model_has_permissions(model_id, model_type);
```

#### 4. `model_has_roles` Table (Pivot)

```sql
CREATE TABLE model_has_roles (
    role_id BIGINT NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id BIGINT NOT NULL,
    PRIMARY KEY (role_id, model_id, model_type),
    CONSTRAINT model_has_roles_role_id_foreign
        FOREIGN KEY (role_id)
        REFERENCES roles(id)
        ON DELETE CASCADE
);

CREATE INDEX model_has_roles_model_id_model_type_index
    ON model_has_roles(model_id, model_type);
```

#### 5. `role_has_permissions` Table (Pivot)

```sql
CREATE TABLE role_has_permissions (
    permission_id BIGINT NOT NULL,
    role_id BIGINT NOT NULL,
    PRIMARY KEY (permission_id, role_id),
    CONSTRAINT role_has_permissions_permission_id_foreign
        FOREIGN KEY (permission_id)
        REFERENCES permissions(id)
        ON DELETE CASCADE,
    CONSTRAINT role_has_permissions_role_id_foreign
        FOREIGN KEY (role_id)
        REFERENCES roles(id)
        ON DELETE CASCADE
);
```

#### 6. Update `users` Table

```sql
-- Add is_admin column if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE;
```

### PostgreSQL-Specific Considerations

1. **AUTO_INCREMENT → BIGSERIAL**: Use `BIGSERIAL` instead of MySQL's `AUTO_INCREMENT`
2. **Case Sensitivity**: PostgreSQL is case-sensitive for string comparisons
3. **Sequences**: Auto-increment uses sequences that may need resetting after data import
4. **JSON Columns**: If storing metadata, use `JSONB` type in PostgreSQL

---

## Configuration Files

### 1. `config/permission.php`

Key configurations to verify:

```php
return [
    'models' => [
        // Use custom Role model with protection logic
        'role' => App\Models\Role::class,
        'permission' => Spatie\Permission\Models\Permission::class,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'role_pivot_key' => null, // Uses 'role_id'
        'permission_pivot_key' => null, // Uses 'permission_id'
        'model_morph_key' => 'model_id',
        'team_foreign_key' => 'team_id',
    ],

    'register_permission_check_method' => true,
    'teams' => false, // Multi-tenancy is disabled
    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key' => 'spatie.permission.cache',
        'store' => 'default',
    ],
];
```

### 2. `config/filament-shield.php`

Key configurations:

```php
return [
    'shield_resource' => [
        'should_register_navigation' => true,
        'slug' => 'shield/roles',
        'navigation_sort' => -1,
        'navigation_badge' => true,
    ],

    'auth_provider_model' => [
        'fqcn' => 'App\\Models\\User',
    ],

    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => false,
        'intercept_gate' => 'before', // Super admin bypasses all checks
    ],

    'permission_prefixes' => [
        'resource' => [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
        ],
    ],

    'entities' => [
        'pages' => true,
        'widgets' => true,
        'resources' => true,
    ],

    'generator' => [
        'option' => 'policies_and_permissions',
    ],

    'exclude' => [
        'enabled' => true,
        'pages' => ['Dashboard'],
        'widgets' => ['AccountWidget', 'FilamentInfoWidget'],
    ],

    'register_role_policy' => [
        'enabled' => true,
    ],
];
```

---

## Models & Relationships

### 1. User Model (`app/Models/User.php`)

```php
<?php

namespace App\Models;

use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasPanelShield, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Check if user has super admin role
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Get user initials for display
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
```

**Key Points:**
- Uses `HasPanelShield` trait from Filament Shield
- Uses `HasRoles` trait from Spatie Permission
- Includes `isSuperAdmin()` helper method
- Observer registered in `AppServiceProvider` for protection

### 2. Role Model (`app/Models/Role.php`)

```php
<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Prevent deletion and modification of super_admin role
     */
    protected static function booted(): void
    {
        static::deleting(function (Role $role) {
            if ($role->name === 'super_admin') {
                throw new \Exception('The Super Admin role cannot be deleted for security reasons.');
            }
        });

        static::updating(function (Role $role) {
            if ($role->getOriginal('name') === 'super_admin') {
                throw new \Exception('The Super Admin role cannot be modified for security reasons.');
            }
        });

        static::saving(function (Role $role) {
            if ($role->getOriginal('name') === 'super_admin' && $role->isDirty('name')) {
                throw new \Exception('The Super Admin role name cannot be changed for security reasons.');
            }
        });
    }
}
```

**Key Points:**
- Extends Spatie's base `Role` model
- Implements event listeners to prevent super_admin modification
- Throws exceptions to block dangerous operations

### 3. UserObserver (`app/Observers/UserObserver.php`)

```php
<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "deleting" event.
     */
    public function deleting(User $user): bool
    {
        $hasSuperAdminRole = $user->roles()->where('name', 'super_admin')->exists();

        if ($hasSuperAdminRole) {
            throw new \Exception('Super Admin users cannot be deleted for security reasons.');
        }

        return true;
    }

    /**
     * Handle the User "saved" event to prevent super_admin role assignment.
     */
    public function saved(User $user): void
    {
        $hasSuperAdminRole = $user->roles()->where('name', 'super_admin')->exists();

        if ($hasSuperAdminRole) {
            $superAdminRole = \App\Models\Role::where('name', 'super_admin')->first();
            if ($superAdminRole) {
                $user->removeRole($superAdminRole);
            }

            throw new \Exception('Super Admin role cannot be assigned to users through the interface for security reasons.');
        }
    }
}
```

**Registration in `app/Providers/AppServiceProvider.php`:**

```php
public function boot(): void
{
    \App\Models\User::observe(\App\Observers\UserObserver::class);
}
```

---

## Policies

All policies follow the same pattern. Here's the structure:

### Policy Template

```php
<?php

namespace App\Policies;

use App\Models\{Resource};
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class {Resource}Policy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_{resource}');
    }

    public function view(User $user, {Resource} ${resource}): bool
    {
        return $user->can('view_{resource}');
    }

    public function create(User $user): bool
    {
        return $user->can('create_{resource}');
    }

    public function update(User $user, {Resource} ${resource}): bool
    {
        return $user->can('update_{resource}');
    }

    public function delete(User $user, {Resource} ${resource}): bool
    {
        return $user->can('delete_{resource}');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_{resource}');
    }

    public function forceDelete(User $user, {Resource} ${resource}): bool
    {
        return $user->can('force_delete_{resource}');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_{resource}');
    }

    public function restore(User $user, {Resource} ${resource}): bool
    {
        return $user->can('restore_{resource}');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_{resource}');
    }

    public function replicate(User $user, {Resource} ${resource}): bool
    {
        return $user->can('replicate_{resource}');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_{resource}');
    }
}
```

### Policies in the Application

The following policies exist:

1. `app/Policies/CustomerPolicy.php`
2. `app/Policies/ExpensePolicy.php`
3. `app/Policies/InvoicePolicy.php`
4. `app/Policies/ProductPolicy.php`
5. `app/Policies/RolePolicy.php`
6. `app/Policies/SettingPolicy.php`
7. `app/Policies/UserPolicy.php`

**Auto-Discovery:** Laravel 12 auto-discovers policies in `app/Policies/` directory.

---

## Super Admin System

### Protection Mechanisms

The super_admin role has multiple layers of protection:

#### 1. Filament Shield Configuration

```php
'super_admin' => [
    'enabled' => true,
    'name' => 'super_admin',
    'define_via_gate' => false,
    'intercept_gate' => 'before', // Bypasses all permission checks
],
```

#### 2. Role Model Protection

- Prevents deletion of super_admin role
- Prevents modification of super_admin role
- Throws exceptions on any attempt

#### 3. User Observer Protection

- Prevents deletion of users with super_admin role
- Prevents assignment of super_admin role through UI
- Automatically removes super_admin role if assigned incorrectly

#### 4. UI-Level Protection

**In RoleResource:**
- Filters out super_admin from table listing
- Blocks edit/delete actions on super_admin role

```php
->modifyQueryUsing(fn ($query) => $query->where('name', '!=', 'super_admin'))
```

**In UserResource:**
- Excludes super_admin role from dropdown options
- Hides super_admin users from listing
- Prevents deletion of super_admin users

```php
->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
```

### Super Admin Creation

Super admin can only be created via seeder or artisan command:

```bash
php artisan shield:super-admin
```

Or through the seeder (`database/seeders/SuperAdminSeeder.php`).

---

## Seeding & Initialization

### Complete Permission List

The application uses these permissions for each resource:

**Resources:** Customer, Expense, Invoice, Product, Role, Setting, User

**Permission Pattern per Resource:**
```
view_any_{resource}
view_{resource}
create_{resource}
update_{resource}
restore_{resource}
restore_any_{resource}
replicate_{resource}
reorder_{resource}
delete_{resource}
delete_any_{resource}
force_delete_{resource}
force_delete_any_{resource}
```

### ShieldSeeder (`database/seeders/ShieldSeeder.php`)

```php
<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $rolesWithPermissions = '[
            {"name":"super_admin","guard_name":"web","permissions":[]},
            {"name":"Admin","guard_name":"web","permissions":[]},
            {"name":"Manager","guard_name":"web","permissions":[]},
            {"name":"Staff","guard_name":"web","permissions":[]}
        ]';

        // Generate all permissions
        $directPermissions = '[
            {"name":"view_any_customer","guard_name":"web"},
            {"name":"view_customer","guard_name":"web"},
            {"name":"create_customer","guard_name":"web"},
            {"name":"update_customer","guard_name":"web"},
            {"name":"restore_customer","guard_name":"web"},
            {"name":"restore_any_customer","guard_name":"web"},
            {"name":"replicate_customer","guard_name":"web"},
            {"name":"reorder_customer","guard_name":"web"},
            {"name":"delete_customer","guard_name":"web"},
            {"name":"delete_any_customer","guard_name":"web"},
            {"name":"force_delete_customer","guard_name":"web"},
            {"name":"force_delete_any_customer","guard_name":"web"},
            // ... (repeat for all resources: expense, invoice, product, setting, user, role)
        ]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            $roleModel = Utils::getRoleModel();
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
```

### SuperAdminSeeder (`database/seeders/SuperAdminSeeder.php`)

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create or find the super_admin role
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);

        // Create the Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@lfpos.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign the super_admin role
        if (! $superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole($superAdminRole);
        }

        $this->command->info('Super Admin created!');
        $this->command->info('Email: superadmin@lfpos.com');
        $this->command->info('Password: password');
    }
}
```

### Seeding Order

In `database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call([
        ShieldSeeder::class,      // 1. Create roles & permissions
        UserSeeder::class,        // 2. Create users (includes SuperAdminSeeder)
        // ... other seeders
    ]);
}
```

---

## PostgreSQL Migration Steps

### Step-by-Step Migration Guide

#### Step 1: Environment Setup

```bash
# Update .env for PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### Step 2: Install Required Packages

```bash
composer require bezhansalleh/filament-shield:"^3.9"
```

#### Step 3: Publish Configuration Files

```bash
# Publish Spatie Permission config
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Publish Filament Shield config
php artisan vendor:publish --tag="filament-shield-config"
```

#### Step 4: Verify/Update Configurations

Check the following files and ensure they match the configurations shown in this document:
- `config/permission.php`
- `config/filament-shield.php`

#### Step 5: Run Migrations

```bash
# Fresh migration
php artisan migrate:fresh

# Or just run the permission migration
php artisan migrate --path=database/migrations/2025_10_06_071851_create_permission_tables.php
```

#### Step 6: Update Config to Use Custom Role Model

In `config/permission.php`:

```php
'models' => [
    'permission' => Spatie\Permission\Models\Permission::class,
    'role' => App\Models\Role::class, // Use custom model
],
```

#### Step 7: Register Panel Shield Plugin

In `app/Providers/Filament/AdminPanelProvider.php`:

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
        ])
        // ... other configurations
}
```

#### Step 8: Register User Observer

In `app/Providers/AppServiceProvider.php`:

```php
public function boot(): void
{
    \App\Models\User::observe(\App\Observers\UserObserver::class);
}
```

#### Step 9: Create/Copy Model Files

Ensure these files exist with the code shown in this document:
- `app/Models/User.php`
- `app/Models/Role.php`
- `app/Observers/UserObserver.php`

#### Step 10: Generate Policies

```bash
# Generate policies for all resources
php artisan shield:generate --all
```

Or create them manually following the policy template shown earlier.

#### Step 11: Create Seeders

Copy or create:
- `database/seeders/ShieldSeeder.php`
- `database/seeders/SuperAdminSeeder.php`

Update `database/seeders/DatabaseSeeder.php` with proper seeding order.

#### Step 12: Seed the Database

```bash
# Clear cache first
php artisan permission:cache-reset

# Run seeders
php artisan db:seed --class=ShieldSeeder
php artisan db:seed --class=SuperAdminSeeder

# Or seed everything
php artisan db:seed
```

#### Step 13: Clear All Caches

```bash
php artisan optimize:clear
php artisan permission:cache-reset
php artisan config:clear
php artisan view:clear
```

#### Step 14: Create RoleResource (if not exists)

```bash
php artisan make:filament-resource Role --generate
```

Then customize it to:
- Hide super_admin from listings
- Block super_admin modification
- Use Shield form components

#### Step 15: Test Access

Login and verify:
1. Super admin has full access
2. Regular users are restricted based on roles
3. Role management works correctly

---

## Testing Checklist

### Functional Tests

- [ ] **Super Admin Login**
    - Login with super admin credentials
    - Verify full access to all resources
    - Verify all CRUD operations work

- [ ] **Role Management**
    - [ ] Create new role
    - [ ] Assign permissions to role
    - [ ] Edit role permissions
    - [ ] Verify super_admin role is hidden from list
    - [ ] Attempt to edit super_admin (should fail)
    - [ ] Attempt to delete super_admin (should fail)
    - [ ] Delete non-super_admin role (should work)

- [ ] **User Management**
    - [ ] Create new user
    - [ ] Assign role to user
    - [ ] Edit user
    - [ ] Change user role
    - [ ] Verify super_admin role not in dropdown
    - [ ] Verify super_admin users hidden from list
    - [ ] Attempt to delete super_admin user via UI (should fail)
    - [ ] Delete non-super_admin user (should work)

- [ ] **Permission Checks Per Resource**

  For Customer, Expense, Invoice, Product, Setting, User, Role:
    - [ ] Create user with specific role
    - [ ] Assign limited permissions
    - [ ] Login as that user
    - [ ] Verify can only perform allowed actions
    - [ ] Verify cannot perform restricted actions
    - [ ] Test view_any permission (can see list)
    - [ ] Test view permission (can see detail)
    - [ ] Test create permission
    - [ ] Test update permission
    - [ ] Test delete permission
    - [ ] Test bulk delete (delete_any)

- [ ] **Policy Integration**
    - [ ] Verify policies are applied to resources
    - [ ] Verify unauthorized users see "Forbidden" errors
    - [ ] Verify authorized users can access resources

- [ ] **Cache Behavior**
    - [ ] Update permissions
    - [ ] Verify changes don't apply until cache cleared
    - [ ] Run `php artisan permission:cache-reset`
    - [ ] Verify changes now apply

### Database Tests

- [ ] **Table Structure**
    - [ ] Verify all 5 permission tables exist
    - [ ] Verify foreign keys are properly set
    - [ ] Verify indexes exist
    - [ ] Verify unique constraints work

- [ ] **Data Integrity**
    - [ ] Verify roles created correctly
    - [ ] Verify permissions created correctly
    - [ ] Verify role-permission relationships
    - [ ] Verify user-role relationships
    - [ ] Verify cascade deletes work (delete role → removes relationships)

### PostgreSQL-Specific Tests

- [ ] **Sequences**
    - [ ] Verify auto-increment works on all tables
    - [ ] Test creating multiple records
    - [ ] Verify no duplicate ID errors

- [ ] **Case Sensitivity**
    - [ ] Verify role lookups work (e.g., 'super_admin')
    - [ ] Verify permission lookups work
    - [ ] Test with mixed-case names

- [ ] **Performance**
    - [ ] Test with large permission sets (100+ permissions)
    - [ ] Test with many users (1000+ users)
    - [ ] Verify cache improves query performance
    - [ ] Check query execution plans for indexes

---

## Common Gotchas

### 1. Permission Cache Issues

**Problem:** Permissions not updating after changes

**Solution:**
```bash
php artisan permission:cache-reset
```

**Prevention:** Always clear cache after:
- Creating/updating roles
- Creating/updating permissions
- Assigning permissions to roles
- Deploying to production

### 2. Super Admin Bypass Not Working

**Problem:** Super admin still checking permissions

**Solution:** Verify in `config/filament-shield.php`:
```php
'super_admin' => [
    'enabled' => true,
    'intercept_gate' => 'before', // Must be 'before'
],
```

### 3. Policies Not Being Applied

**Problem:** Everyone can access everything

**Solution:**
- Verify policies exist in `app/Policies/`
- Verify policy naming matches model name (e.g., `CustomerPolicy` for `Customer` model)
- Laravel 12 auto-discovers policies, no manual registration needed
- Clear config cache: `php artisan config:clear`

### 4. Guard Name Mismatches

**Problem:** Permissions not working, roles not found

**Solution:**
- All roles and permissions must use same guard (usually 'web')
- Verify in database: `SELECT * FROM roles WHERE guard_name != 'web'`
- Recreate with correct guard if needed

### 5. PostgreSQL Sequence Issues After Import

**Problem:** "Duplicate key value violates unique constraint" after importing data

**Solution:**
```sql
-- Reset sequences to current max ID
SELECT setval('permissions_id_seq', (SELECT MAX(id) FROM permissions));
SELECT setval('roles_id_seq', (SELECT MAX(id) FROM roles));
```

Or use the custom artisan command:
```bash
php artisan db:sync-sequences
```

### 6. Model Relationships Not Loading

**Problem:** `$user->roles` returns empty even though data exists

**Solution:**
- Clear model cache: `php artisan optimize:clear`
- Verify `HasRoles` trait is used on User model
- Check foreign keys exist in database
- Verify `model_type` in pivot tables uses correct namespace (e.g., 'App\Models\User')

### 7. Filament Resource Not Respecting Permissions

**Problem:** Resource shows for users without permission

**Solution:**
- Ensure resource uses policy
- Verify policy methods return correct boolean
- Use `$user->can()` not `$user->hasPermissionTo()` in policies
- Clear view cache: `php artisan view:clear`

### 8. Super Admin Role Gets Assigned via UI

**Problem:** UserObserver not preventing assignment

**Solution:**
- Verify observer is registered in AppServiceProvider
- Check observer method names match events: `saved()`, `deleting()`
- Throw exceptions, don't just return false

### 9. Migration Fails with Foreign Key Constraint

**Problem:** Cannot create foreign keys during migration

**Solution:**
```php
// In migration, use this order:
Schema::create('permissions', ...);
Schema::create('roles', ...);
Schema::create('model_has_permissions', ...); // After permissions
Schema::create('model_has_roles', ...); // After roles
Schema::create('role_has_permissions', ...); // After both
```

### 10. Team Feature Conflicts

**Problem:** Errors about missing team_id column

**Solution:**
- Verify in `config/permission.php`: `'teams' => false`
- Don't use team-related methods
- If enabling teams later, run migration to add team columns

---

## Additional Resources

### Official Documentation

- [Filament Shield Documentation](https://filamentphp.com/plugins/bezhansalleh-shield)
- [Spatie Permission Documentation](https://spatie.be/docs/laravel-permission)
- [Filament Documentation](https://filamentphp.com/docs)
- [Laravel Policy Documentation](https://laravel.com/docs/authorization)

### Useful Commands

```bash
# Generate Shield resources
php artisan shield:generate --all

# Create super admin
php artisan shield:super-admin

# Create seeder from existing permissions
php artisan shield:seeder

# List all permissions
php artisan permission:show

# Create role via artisan
php artisan permission:create-role Admin web

# Create permission via artisan
php artisan permission:create-permission "edit articles" web

# Cache permissions
php artisan config:cache
php artisan permission:cache-reset
```

### Debugging Tips

1. **Check User Permissions:**
```php
// In tinker
$user = User::find(1);
$user->getAllPermissions(); // All permissions (direct + via roles)
$user->getPermissionsViaRoles(); // Only from roles
$user->roles; // All roles
$user->can('create_customer'); // Test specific permission
```

2. **Check Role Permissions:**
```php
$role = Role::findByName('Admin');
$role->permissions; // All permissions for role
$role->users; // All users with this role
```

3. **Verify Cache:**
```php
// Check if cache is being used
\Illuminate\Support\Facades\Cache::get('spatie.permission.cache');
```

4. **Database Queries:**
```sql
-- All permissions for user ID 1
SELECT p.* FROM permissions p
INNER JOIN model_has_permissions mp ON p.id = mp.permission_id
WHERE mp.model_id = 1 AND mp.model_type = 'App\\Models\\User';

-- All roles for user ID 1
SELECT r.* FROM roles r
INNER JOIN model_has_roles mr ON r.id = mr.role_id
WHERE mr.model_id = 1 AND mr.model_type = 'App\\Models\\User';

-- All permissions for role 'Admin'
SELECT p.* FROM permissions p
INNER JOIN role_has_permissions rp ON p.id = rp.permission_id
INNER JOIN roles r ON r.id = rp.role_id
WHERE r.name = 'Admin';
```

---

## Version Compatibility

This RBAC system is tested with:

- **PHP:** 8.4.13
- **Laravel:** 12.34.0
- **Filament:** 3.3.43
- **Filament Shield:** 3.9.x
- **Spatie Permission:** 6.x (dependency of Shield)
- **Database:** MySQL 8.x (source), PostgreSQL 12+ (target)

---

## Conclusion

This RBAC system provides:

1. **Granular Permissions** - Per-resource, per-action control
2. **Role-Based Management** - Easy permission grouping via roles
3. **Super Admin Protection** - Multi-layer security for admin role
4. **Filament Integration** - Seamless UI for permission management
5. **Policy Enforcement** - Laravel-native authorization
6. **Cache Optimization** - Fast permission checking with 24-hour cache
7. **PostgreSQL Ready** - Compatible with PostgreSQL database

By following this guide, you can replicate the exact RBAC setup in any PostgreSQL-based environment while maintaining all security measures and functionality.

---

**Document Version:** 1.0
**Last Updated:** 2025-11-25
**Author:** System Analysis of LfPOS Application
