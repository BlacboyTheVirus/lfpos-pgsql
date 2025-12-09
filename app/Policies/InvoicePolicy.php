<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Allow all authenticated users to perform any action.
     * RBAC disabled - using basic authentication only.
     */
    public function before(User $user, string $ability): ?bool
    {
        return true;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_invoice');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('view_invoice');
    }

    public function create(User $user): bool
    {
        return $user->can('create_invoice');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('update_invoice');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can('delete_invoice');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_invoice');
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->can('force_delete_invoice');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_invoice');
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->can('restore_invoice');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_invoice');
    }

    public function replicate(User $user, Invoice $invoice): bool
    {
        return $user->can('replicate_invoice');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_invoice');
    }
}
