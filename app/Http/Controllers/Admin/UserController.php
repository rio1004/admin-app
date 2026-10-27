<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ManageUsers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'sort' => ['nullable', 'in:name,email,created_at'], 'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $users = User::query()->select(['id', 'name', 'username', 'email'])
            ->with(['roles.permissions:id,name,guard_name', 'permissions:id,name,guard_name'])
            ->when(isset($filters['search']) && $filters['search'] !== '', fn ($query) => $query
                ->where(fn ($query) => $query->where('name', 'like', '%'.$filters['search'].'%')->orWhere('email', 'like', '%'.$filters['search'].'%')))
            ->when($filters['role_id'] ?? null, fn ($query, $id) => $query->whereHas('roles', fn ($query) => $query->where('roles.id', $id)->where('guard_name', 'web')))
            ->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')->orderBy('id')
            ->paginate(10)->withQueryString();
        $users->through(fn (User $user) => $this->payload($request, $user));

        return Inertia::render('admin/users/index', ['users' => $users, 'filters' => $filters,
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(['id', 'name']),
            'canCreate' => $request->user()->can('create', User::class)]);
    }

    public function create(Request $request): Response
    {
        return $this->form($request);
    }

    public function edit(Request $request, User $user): Response
    {
        return $this->form($request, $user);
    }

    private function form(Request $request, ?User $user = null): Response
    {
        $user?->load(['roles.permissions', 'permissions']);
        $actor = $request->user();
        $canAssign = $actor->can('roles.update');
        $roles = $canAssign ? Role::query()->where('guard_name', 'web')->with('permissions')->orderBy('name')->get()
            ->map(fn (Role $role) => ['id' => $role->id, 'name' => $role->name,
                'grantable' => ($role->name !== AdminPermissions::ADMIN_ROLE || $actor->hasRole(AdminPermissions::ADMIN_ROLE, 'web'))
                    && $role->permissions->every(fn ($permission) => in_array($permission->name, AdminPermissions::all(), true) && $actor->can($permission->name))]) : [];

        return Inertia::render('admin/users/form', [
            'user' => $user ? $this->payload($request, $user) : null, 'roles' => $roles,
            'canAssignRoles' => $canAssign, 'canViewUsers' => $actor->can('viewAny', User::class),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(Request $request, User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'username' => $user->username, 'email' => $user->email,
            'roles' => $user->roles->map(fn ($role) => $role->only(['id', 'name']))->values(),
            'is_self' => $request->user()->id === $user->id,
            'can' => ['update' => $request->user()->can('update', $user), 'delete' => $request->user()->can('delete', $user)]];
    }

    public function store(UserRequest $request, ManageUsers $users): RedirectResponse
    {
        $user = $this->save($request, $users);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'User created.']);

        return $this->destination($request, $user);
    }

    public function update(UserRequest $request, User $user, ManageUsers $users): RedirectResponse
    {
        $user = $this->save($request, $users, $user);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'User updated.']);

        return $this->destination($request, $user);
    }

    private function save(UserRequest $request, ManageUsers $users, ?User $user = null): User
    {
        $data = $request->validated();
        $roleIds = array_key_exists('roles', $data) ? array_values(array_map(intval(...), $data['roles'])) : null;

        return $users->save($request->user(), $user, ['name' => $data['name'], 'username' => $data['username'] ?? $user?->username, 'email' => $data['email'], 'password' => $data['password'] ?? null], $roleIds);
    }

    private function destination(Request $request, User $target): RedirectResponse
    {
        $actor = User::query()->findOrFail($request->user()->id);
        if ($actor->can('viewAny', User::class)) {
            return to_route('admin.users.index');
        }
        if ($actor->can('update', $target->fresh())) {
            return to_route('admin.users.edit', $target);
        }
        if ($actor->can('create', User::class)) {
            return to_route('admin.users.create');
        }

        return to_route('profile.edit');
    }

    public function destroy(Request $request, User $user, ManageUsers $users): RedirectResponse
    {
        $users->delete($request->user(), $user);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'User deleted.']);

        return $request->user()->can('viewAny', User::class) ? to_route('admin.users.index') : to_route('profile.edit');
    }
}
