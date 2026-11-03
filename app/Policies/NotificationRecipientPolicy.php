<?php

namespace App\Policies;

use App\Models\User;

/** Who receives booking emails: managed by Sales Managers (and Super Admin). */
class NotificationRecipientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leads.assign');
    }

    public function view(User $user): bool
    {
        return $user->can('leads.assign');
    }

    public function create(User $user): bool
    {
        return $user->can('leads.assign');
    }

    public function update(User $user): bool
    {
        return $user->can('leads.assign');
    }

    public function delete(User $user): bool
    {
        return $user->can('leads.assign');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('leads.assign');
    }
}
