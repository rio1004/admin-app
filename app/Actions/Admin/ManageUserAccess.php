<?php

namespace App\Actions\Admin;

use App\Models\Role;
use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ManageUserAccess
{
    public function __construct(private AccessLock $lock) {}

    /** @param list<int> $roleIds */
    public function assignRoles(User $actor, User $target, array $roleIds): void
    {
        $this->lock->run(function () use ($actor, $target, $roleIds) {
            $actor = User::query()->findOrFail($actor->id);
            $target = User::query()->findOrFail($target->id);
            Gate::forUser($actor)->authorize('admin.access');
            Gate::forUser($actor)->authorize('roles.update');
            $roles = Role::query()->where('guard_name', 'web')->whereIn('id', $roleIds)->get();
            if ($roles->count() !== count(array_unique($roleIds))) {
                throw ValidationException::withMessages(['roles' => 'Select valid roles.']);
            }
            $wasAdmin = $target->hasRole(AdminPermissions::ADMIN_ROLE, 'web');
            $willBeAdmin = $roles->contains('name', AdminPermissions::ADMIN_ROLE);
            if (($wasAdmin || $willBeAdmin) && ! $actor->hasRole(AdminPermissions::ADMIN_ROLE, 'web')) {
                abort(403, 'Only an administrator may change administrator assignments.');
            }
            $currentRoles = Role::query()->whereIn('id', $target->roles()->pluck('roles.id'))->get();
            foreach ($roles->merge($currentRoles)->unique('id') as $role) {
                foreach ($role->permissions as $permission) {
                    abort_unless(in_array($permission->name, AdminPermissions::all(), true) && $actor->can($permission->name), 403);
                }
            }
            if ($wasAdmin && ! $willBeAdmin) {
                $this->assertAnotherAdmin($target);
            }
            $target->syncRoles($roles);
        });
    }

    public function deleteAccount(User $target): void
    {
        $this->lock->run(function () use ($target) {
            $target = User::query()->findOrFail($target->id);
            if ($target->hasRole(AdminPermissions::ADMIN_ROLE, 'web')) {
                $this->assertAnotherAdmin($target);
            }
            $target->delete();
            DB::table('sessions')->where('user_id', $target->id)->delete();
            DB::table('password_reset_tokens')->where('email', $target->email)->delete();
        });
    }

    private function assertAnotherAdmin(User $target): void
    {
        if (! User::role(AdminPermissions::ADMIN_ROLE, 'web')->whereKeyNot($target->id)->exists()) {
            throw ValidationException::withMessages(['user' => 'The final administrator cannot be removed.', 'roles' => 'The final administrator cannot be removed.', 'password' => 'The final administrator cannot delete their account.']);
        }
    }
}
