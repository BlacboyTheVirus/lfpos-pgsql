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
