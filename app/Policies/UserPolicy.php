<?php

namespace App\Policies;

use App\Models\User;
use App\Support\AdminPermissions;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('admin.access') && $user->can('users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('admin.access') && $user->can('users.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('admin.access') && $user->can('users.update') && $this->canManage($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->id !== $target->id && $user->can('admin.access') && $user->can('users.delete') && $this->canManage($user, $target);
    }

    private function canManage(User $user, User $target): bool
    {
        if ($target->hasRole(AdminPermissions::ADMIN_ROLE, 'web') && ! $user->hasRole(AdminPermissions::ADMIN_ROLE, 'web')) {
            return false;
        }
        foreach ($target->getAllPermissions() as $permission) {
            if (! in_array($permission->name, AdminPermissions::all(), true) || ! $user->can($permission->name)) {
                return false;
            }
        }

        return true;
    }
}
