<?php

namespace App\Policies;

use App\Models\User;
use App\Support\SystemRole;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->can('users.update')) {
            return false;
        }

        // Technical user management must never modify or take over business identities.
        if ($model->hasBusinessIdentity()) {
            return false;
        }

        if ($model->hasRole(SystemRole::SUPER_ADMIN) && ! $user->hasRole(SystemRole::SUPER_ADMIN)) {
            return false;
        }

        return true;
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->can('users.delete')) {
            return false;
        }

        if ($user->is($model) || $model->hasBusinessIdentity() || $model->isProtectedDemoPersona() || $model->hasRole(SystemRole::SUPER_ADMIN)) {
            return false;
        }

        return true;
    }

    public function updateRoles(User $user, User $model): bool
    {
        if (! $user->can('users.update') || ! $user->can('roles.update')) {
            return false;
        }

        // Technical roles cannot be granted to or modified on Sunday School business identities.
        if ($model->hasBusinessIdentity()) {
            return false;
        }

        if ($model->hasRole(SystemRole::SUPER_ADMIN) && ! $user->hasRole(SystemRole::SUPER_ADMIN)) {
            return false;
        }

        return true;
    }
}
