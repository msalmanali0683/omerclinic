<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['hospital-admin', 'accountant', 'patient']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->hasRole('hospital-admin') || $user->hasRole('accountant')) {
            return true;
        }

        if ($user->hasRole('patient')) {
            return $invoice->patient->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('accountant') || $user->can('create invoice');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('accountant') || $user->can('edit invoice');
    }

    public function pay(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('accountant') || $user->can('receive payment');
    }
}
