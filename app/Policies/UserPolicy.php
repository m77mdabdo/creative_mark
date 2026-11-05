<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/** Staff accounts: Super Admin only (users.manage). Nobody can delete themselves or the last active Super Admin. */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function view(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can('users.manage') && $user->isNot($target) && ! self::isLastSuperAdmin($target);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    /** The only active Super Admin may not be removed, demoted or switched off, or nobody could manage the site. */
    public static function isLastSuperAdmin(User $target): bool
    {
        return $target->is_active && $target->hasRole(Role::SuperAdmin->value)
            && User::role(Role::SuperAdmin->value)->where('is_active', true)->count() <= 1;
    }
}
