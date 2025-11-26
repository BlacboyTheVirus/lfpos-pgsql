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
