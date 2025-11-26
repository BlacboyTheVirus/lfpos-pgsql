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

        // Define resources
        $resources = [
            'customer',
            'expense',
            'invoice',
            'product',
            'role',
            'setting',
            'user',
        ];

        // Define permission actions
        $actions = [
            'view_any',
            'view',
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
        ];

        // Define widgets
        $widgets = [
            'dashboard_stats_widget',
            'invoice_totals_chart',
            'product_revenue_chart',
            'top_customers_widget',
            'date_range_filter_widget',
        ];

        // Generate all permissions
        $permissionsArray = [];
        $allPermissionNames = [];

        // Resource permissions
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissionName = "{$action}_{$resource}";
                $permissionsArray[] = [
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ];
                $allPermissionNames[] = $permissionName;
            }
        }

        // Widget permissions
        foreach ($widgets as $widget) {
            $permissionName = "view_{$widget}";
            $permissionsArray[] = [
                'name' => $permissionName,
                'guard_name' => 'web',
            ];
            $allPermissionNames[] = $permissionName;
        }

        $this->command->info('Creating permissions...');
        static::makeDirectPermissions($permissionsArray);

        // Create roles with permissions
        $rolesWithPermissions = [
            ['name' => 'super_admin', 'guard_name' => 'web', 'permissions' => $allPermissionNames],
            ['name' => 'Admin', 'guard_name' => 'web', 'permissions' => $allPermissionNames],
            ['name' => 'Manager', 'guard_name' => 'web', 'permissions' => []],
            ['name' => 'Staff', 'guard_name' => 'web', 'permissions' => []],
        ];

        $this->command->info('Creating roles and assigning permissions...');
        static::makeRolesWithPermissions($rolesWithPermissions);

        $this->command->info('Shield Seeding Completed!');
        $this->command->info('Total Permissions Created: '.count($permissionsArray));
        $this->command->info('Total Roles Created: '.count($rolesWithPermissions));
    }

    protected static function makeRolesWithPermissions(array $rolesWithPermissions): void
    {
        $roleModel = Utils::getRoleModel();
        $permissionModel = Utils::getPermissionModel();

        foreach ($rolesWithPermissions as $rolePlusPermission) {
            $role = $roleModel::firstOrCreate([
                'name' => $rolePlusPermission['name'],
                'guard_name' => $rolePlusPermission['guard_name'],
            ]);

            if (! empty($rolePlusPermission['permissions'])) {
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

    public static function makeDirectPermissions(array $directPermissions): void
    {
        $permissionModel = Utils::getPermissionModel();

        foreach ($directPermissions as $permission) {
            if ($permissionModel::whereName($permission['name'])->doesntExist()) {
                $permissionModel::create([
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ]);
            }
        }
    }
}
