<?php

namespace App\Actions\Admin;

use App\Models\Role;
use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

final class ManageRoles
{
    public function __construct(private AccessLock $lock) {}

    /** @param list<string> $permissions */
    public function save(User $actor, ?Role $role, string $name, array $permissions): Role
    {
        $name = trim($name);
        $saved = $this->lock->run(function () use ($actor, $role, $name, $permissions) {
            $actor = User::query()->findOrFail($actor->id);
            $role = $role ? Role::query()->findOrFail($role->id) : null;
            Gate::forUser($actor)->authorize($role ? 'update' : 'create', $role ?? Role::class);

            if (strcasecmp($name, AdminPermissions::ADMIN_ROLE) === 0) {
                throw ValidationException::withMessages(['name' => 'The Admin role is reserved.']);
            }

            if (Role::query()->where('guard_name', 'web')->where('name', $name)
                ->when($role, fn ($query) => $query->whereKeyNot($role->id))->exists()) {
                throw ValidationException::withMessages(['name' => 'The role name has already been taken.']);
            }

            // A manager cannot alter permissions outside their own authority,
            // including permissions removed from an existing role.
            $changed = array_unique([...$permissions, ...($role?->permissions->pluck('name')->all() ?? [])]);
            foreach ($changed as $permission) {
                if (! in_array($permission, AdminPermissions::all(), true) || ! $actor->can($permission)) {
                    throw ValidationException::withMessages(['permissions' => 'You may only manage permissions you hold.']);
                }
            }

            $role ??= new Role;
            $role->name = $name;
            $role->guard_name = 'web';
            $role->save();
            $role->syncPermissions($permissions);

            return $role;
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $saved;
    }

    public function delete(User $actor, Role $role): void
    {
        $this->lock->run(function () use ($actor, $role) {
            $actor = User::query()->findOrFail($actor->id);
            $role = Role::query()->findOrFail($role->id);
            Gate::forUser($actor)->authorize('delete', $role);
            if ($role->users()->exists()) {
                throw ValidationException::withMessages(['role' => 'Reassign this role’s users before deleting it.']);
            }
            foreach ($role->permissions as $permission) {
                abort_unless(in_array($permission->name, AdminPermissions::all(), true) && $actor->can($permission->name), 403);
            }
            $role->delete();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
