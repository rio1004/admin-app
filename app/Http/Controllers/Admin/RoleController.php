<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ManageRoles;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:name,created_at'], 'direction' => ['nullable', 'in:asc,desc']]);
        $roles = Role::query()->where('guard_name', 'web')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->with('permissions:id,name')->withCount('users')
            ->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')->orderBy('id')
            ->paginate(10)->withQueryString();
        $roles->through(fn (Role $role) => [
            'id' => $role->id, 'name' => $role->name,
            'permissions' => $role->permissions->pluck('name'), 'users_count' => $role->users_count,
            'protected' => $role->name === AdminPermissions::ADMIN_ROLE,
            'can' => ['update' => $request->user()->can('update', $role), 'delete' => $request->user()->can('delete', $role)],
        ]);

        return Inertia::render('admin/roles/index', ['roles' => $roles, 'filters' => $filters,
            'canCreate' => $request->user()->can('create', Role::class)]);
    }

    public function create(Request $request): Response
    {
        return $this->form($request);
    }

    public function edit(Request $request, Role $role): Response
    {
        return $this->form($request, $role);
    }

    private function form(Request $request, ?Role $role = null): Response
    {
        return Inertia::render('admin/roles/form', [
            'role' => $role ? ['id' => $role->id, 'name' => $role->name, 'permissions' => $role->permissions->pluck('name')] : null,
            'permissions' => array_map(fn ($name) => ['name' => $name, 'grantable' => $request->user()->can($name)], AdminPermissions::all()),
            'canViewRoles' => $request->user()->can('viewAny', Role::class),
        ]);
    }

    public function store(RoleRequest $request, ManageRoles $roles): RedirectResponse
    {
        $data = $request->validated();
        $roles->save($request->user(), null, $data['name'], $data['permissions']);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role created.']);

        return $this->destination($request);
    }

    public function update(RoleRequest $request, Role $role, ManageRoles $roles): RedirectResponse
    {
        $data = $request->validated();
        $roles->save($request->user(), $role, $data['name'], $data['permissions']);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role updated.']);

        return $this->destination($request, $role);
    }

    public function destroy(Request $request, Role $role, ManageRoles $roles): RedirectResponse
    {
        $roles->delete($request->user(), $role);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Role deleted.']);

        return $this->destination($request);
    }

    private function destination(Request $request, ?Role $role = null): RedirectResponse
    {
        // Editing a role may revoke the current user's own workspace access.
        $user = User::query()->findOrFail($request->user()->id);
        if ($role && $user->can('update', $role)) {
            return to_route('admin.roles.edit', $role);
        }
        if ($user->can('viewAny', Role::class)) {
            return to_route('admin.roles.index');
        }
        if ($user->can('create', Role::class)) {
            return to_route('admin.roles.create');
        }

        return to_route('profile.edit');
    }
}
