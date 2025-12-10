<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * Automatically assign panel_user role to all new users.
     */
    public function created(User $user): void
    {
        // Only assign if panel_user role exists and user doesn't already have it
        if (!$user->hasRole('panel_user')) {
            $user->assignRole('panel_user');
        }
    }

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(User $user): bool
    {
        // Load the roles relationship
        $user->load('roles');

        // Check if user has super_admin role
        $hasSuperAdminRole = $user->roles->contains('name', 'super_admin');

        if ($hasSuperAdminRole) {
            throw new \Exception('Super Admin users cannot be deleted for security reasons.');
        }

        return true;
    }
}
