<?php

use App\Actions\Admin\ManageRoles;
use App\Models\Role;
use App\Models\User;
use App\Support\AdminPermissions;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

test('seeders are idempotent and never create accounts', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);
    expect(Role::count())->toBe(1)->and(Permission::count())->toBe(count(AdminPermissions::all()))
        ->and(User::count())->toBe(1);
    expect(Role::findByName('Admin')->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect(AdminPermissions::all())->sort()->values()->all());
});

test('first admin command promotes an existing account without changing credentials', function () {
    $user = User::factory()->create();
    $password = $user->password;
    $this->artisan('admin:make', ['email' => $user->email])->assertSuccessful();
    $this->artisan('admin:make', ['email' => $user->email])->assertSuccessful();
    expect($user->fresh()->hasRole('Admin'))->toBeTrue()->and($user->fresh()->password)->toBe($password);
    expect(DB::table('model_has_roles')->where('model_id', $user->id)->count())->toBe(1);
});

test('first admin setup does not silently create an account', function () {
    $this->artisan('admin:make', ['email' => 'missing@example.com', '--no-interaction' => true])->assertFailed();
    expect(User::where('email', 'missing@example.com')->exists())->toBeFalse();
});

test('first admin setup creates an account only with explicit interactive credentials', function () {
    $this->artisan('admin:make', ['email' => 'first@example.com', '--create' => true])
        ->expectsQuestion('Name', 'First Admin')
        ->expectsQuestion('Password (minimum 12 characters, mixed case, number and symbol)', 'TestOnly!Password123')
        ->expectsQuestion('Confirm password', 'TestOnly!Password123')
        ->assertSuccessful();
    $user = User::where('email', 'first@example.com')->firstOrFail();
    expect($user->hasRole('Admin'))->toBeTrue()->and(Hash::check('TestOnly!Password123', $user->password))->toBeTrue();
});

test('first admin setup rejects invalid email and weak passwords', function () {
    $this->artisan('admin:make', ['email' => 'invalid'])->assertFailed();
    $this->artisan('admin:make', ['email' => 'weak@example.com', '--create' => true])
        ->expectsQuestion('Name', 'First Admin')
        ->expectsQuestion('Password (minimum 12 characters, mixed case, number and symbol)', 'weak')
        ->expectsQuestion('Confirm password', 'weak')
        ->assertFailed();
    expect(User::where('email', 'weak@example.com')->exists())->toBeFalse();
});

test('write authorization reloads revoked grants after acquiring the access lock', function () {
    $managerRole = Role::create(['name' => 'Manager', 'guard_name' => 'web']);
    $managerRole->givePermissionTo(['admin.access', 'roles.create']);
    $manager = User::factory()->create();
    $manager->assignRole($managerRole);
    expect($manager->can('roles.create'))->toBeTrue();
    $permission = Permission::findByName('roles.create');
    DB::table('role_has_permissions')->where('role_id', $managerRole->id)->where('permission_id', $permission->id)->delete();
    expect(fn () => app(ManageRoles::class)->save($manager, null, 'Forbidden', []))->toThrow(AuthorizationException::class);
    expect(Role::where('name', 'Forbidden')->exists())->toBeFalse();
});

test('guests and ordinary users cannot access any role endpoint', function () {
    $role = Role::create(['name' => 'Viewer', 'guard_name' => 'web']);
    $requests = [['get', '/admin/roles'], ['get', '/admin/roles/create'], ['get', "/admin/roles/$role->id/edit"], ['post', '/admin/roles'], ['put', "/admin/roles/$role->id"], ['delete', "/admin/roles/$role->id"], ['put', "/admin/users/{$this->admin->id}/roles"]];
    foreach ($requests as [$method, $url]) {
        $this->{$method}($url)->assertRedirect(route('login'));
    }
    $this->actingAs(User::factory()->create());
    foreach ($requests as [$method, $url]) {
        $this->{$method}($url)->assertForbidden();
    }
});

test('dashboard requires both access and dashboard permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('dashboard.view');
    $this->actingAs($user)->get('/dashboard')->assertForbidden();
    $user->syncPermissions(['admin.access']);
    $this->get('/dashboard')->assertForbidden();
    $user->givePermissionTo('dashboard.view');
    $this->get('/dashboard')->assertOk();
});

test('roles support authorized create edit update and delete', function () {
    $this->actingAs($this->admin)->get('/admin/roles/create')->assertOk();
    $this->post('/admin/roles', ['name' => 'Content Editor', 'permissions' => ['admin.access', 'items.view']])->assertSessionHasNoErrors()->assertRedirect(route('admin.roles.index'));
    $role = Role::findByName('Content Editor');
    $this->get("/admin/roles/$role->id/edit")->assertOk();
    $this->put("/admin/roles/$role->id", ['name' => 'Content Reviewer', 'permissions' => ['items.view']])->assertSessionHasNoErrors();
    expect($role->fresh()->name)->toBe('Content Reviewer')->and($role->fresh()->permissions->pluck('name')->all())->toBe(['items.view']);
    $this->delete("/admin/roles/$role->id")->assertSessionHasNoErrors();
    expect($role->fresh())->toBeNull();
});

test('each role action requires its own permission', function () {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(['admin.access', 'roles.view']);
    $role = Role::create(['name' => 'Viewer', 'guard_name' => 'web']);
    $this->actingAs($viewer)->get('/admin/roles')->assertOk();
    $this->get('/admin/roles/create')->assertForbidden();
    $this->get("/admin/roles/$role->id/edit")->assertForbidden();
    $this->post('/admin/roles', ['name' => 'New role', 'permissions' => []])->assertForbidden();
    $this->put("/admin/roles/$role->id", ['name' => 'Other', 'permissions' => []])->assertForbidden();
    $this->delete("/admin/roles/$role->id")->assertForbidden();
});

test('editing a role that revokes own access returns to an accessible page', function () {
    $role = Role::create(['name' => 'Manager', 'guard_name' => 'web']);
    $role->givePermissionTo(['admin.access', 'roles.update', 'roles.view']);
    $manager = User::factory()->create();
    $manager->assignRole($role);
    $this->actingAs($manager)->put("/admin/roles/$role->id", ['name' => 'Former manager', 'permissions' => []])
        ->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));
});

test('role name validation ignores the current role but rejects duplicates and reserved names', function () {
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $this->actingAs($this->admin)->put("/admin/roles/$role->id", ['name' => 'Editor', 'permissions' => []])->assertSessionHasNoErrors();
    foreach (['Editor', 'Admin', 'admin'] as $name) {
        $this->post('/admin/roles', ['name' => $name, 'permissions' => []])->assertSessionHasErrors('name');
    }
});

test('unknown permissions and forged fields are rejected', function () {
    Permission::create(['name' => 'unregistered.power', 'guard_name' => 'web']);
    $this->actingAs($this->admin)->post('/admin/roles', ['name' => 'Escalation', 'permissions' => ['unregistered.power']])->assertSessionHasErrors('permissions.0');
    $this->post('/admin/roles', ['name' => 'Escalation', 'permissions' => [], 'guard_name' => 'api', 'users' => [$this->admin->id]])->assertSessionHasErrors(['guard_name', 'users']);
    expect(Role::where('name', 'Escalation')->exists())->toBeFalse();
});

test('managers cannot grant or strip permissions they do not hold', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo(['admin.access', 'roles.create', 'roles.update', 'roles.delete']);
    $privileged = Role::create(['name' => 'Privileged', 'guard_name' => 'web']);
    $privileged->givePermissionTo('users.delete');
    $this->actingAs($manager)->post('/admin/roles', ['name' => 'Escalation', 'permissions' => ['users.delete']])->assertSessionHasErrors('permissions');
    $this->put("/admin/roles/$privileged->id", ['name' => 'Stripped', 'permissions' => []])->assertSessionHasErrors('permissions');
    $this->delete("/admin/roles/$privileged->id")->assertForbidden();
    expect($privileged->fresh()->name)->toBe('Privileged');
});

test('Admin role is immutable and its name does not bypass permission checks', function () {
    $role = Role::findByName('Admin');
    $this->actingAs($this->admin)->get("/admin/roles/$role->id/edit")->assertForbidden();
    $this->put("/admin/roles/$role->id", ['name' => 'Renamed', 'permissions' => []])->assertForbidden();
    $this->delete("/admin/roles/$role->id")->assertForbidden();
    $role->revokePermissionTo('dashboard.view');
    $this->admin->unsetRelation('roles')->unsetRelation('permissions');
    $this->get('/dashboard')->assertForbidden();
});

test('assigned roles cannot be deleted', function () {
    $role = Role::create(['name' => 'Assigned', 'guard_name' => 'web']);
    User::factory()->create()->assignRole($role);
    $this->actingAs($this->admin)->delete("/admin/roles/$role->id")->assertSessionHasErrors('role');
    expect($role->fresh())->not->toBeNull();
});

test('role assignment requires roles update even with users update', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo(['admin.access', 'users.update']);
    $target = User::factory()->create();
    $this->actingAs($manager)->put("/admin/users/$target->id/roles", ['roles' => [Role::findByName('Admin')->id]])->assertForbidden();
    expect($target->fresh()->roles)->toHaveCount(0);
});

test('non administrators cannot assign Admin or permissions outside their authority', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo(['admin.access', 'roles.update']);
    $target = User::factory()->create();
    $privileged = Role::create(['name' => 'Privileged', 'guard_name' => 'web']);
    $privileged->givePermissionTo('users.delete');
    $this->actingAs($manager)->put("/admin/users/$target->id/roles", ['roles' => [Role::findByName('Admin')->id]])->assertForbidden();
    $this->put("/admin/users/$target->id/roles", ['roles' => [$privileged->id]])->assertForbidden();
    $this->put("/admin/users/$target->id/roles", ['roles' => [999999]])->assertSessionHasErrors('roles.0');
    expect($target->fresh()->roles)->toHaveCount(0);
});

test('authorized role assignment succeeds and final administrator demotion is rejected', function () {
    $target = User::factory()->create();
    $role = Role::create(['name' => 'Viewer', 'guard_name' => 'web']);
    $role->givePermissionTo('items.view');
    $this->actingAs($this->admin)->put("/admin/users/$target->id/roles", ['roles' => [$role->id]])->assertSessionHasNoErrors();
    expect($target->fresh()->hasRole('Viewer'))->toBeTrue();
    $this->put("/admin/users/{$this->admin->id}/roles", ['roles' => []])->assertSessionHasErrors('roles');
    expect($this->admin->fresh()->hasRole('Admin'))->toBeTrue();
});

test('one admin can be demoted but the remaining administrator is protected', function () {
    $other = User::factory()->create();
    $other->assignRole('Admin');
    $this->actingAs($this->admin)->put("/admin/users/$other->id/roles", ['roles' => []])->assertSessionHasNoErrors();
    expect($other->fresh()->hasRole('Admin'))->toBeFalse();
    $this->put("/admin/users/{$this->admin->id}/roles", ['roles' => []])->assertSessionHasErrors('roles');
});

test('final administrator cannot self delete and remains logged in', function () {
    $this->actingAs($this->admin)->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrors('password');
    $this->assertAuthenticated();
    expect($this->admin->fresh())->not->toBeNull();
    User::factory()->create()->assignRole('Admin');
    $this->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasNoErrors();
    $this->assertGuest();
});

test('profile writes cannot assign roles or permissions', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Forged', 'email' => $user->email, 'roles' => ['Admin'], 'permissions' => ['admin.access']])->assertSessionHasErrors(['roles', 'permissions']);
    expect($user->fresh()->hasRole('Admin'))->toBeFalse()->and($user->fresh()->name)->toBe($user->name);
});

test('shared props expose only defined capabilities and safe user fields', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'dashboard.view']);
    $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')->where('auth.permissions', ['admin.access', 'dashboard.view'])
        ->missing('auth.user.password')->missing('auth.user.remember_token')->missing('auth.user.roles'));
});

test('role listing filters paginates preserves queries and rejects unsafe sorting', function () {
    foreach (range(1, 12) as $i) {
        Role::create(['name' => sprintf('Team %02d', $i), 'guard_name' => 'web']);
    }
    $this->actingAs($this->admin)->get('/admin/roles?search=Team&sort=name&direction=asc')->assertInertia(fn (Assert $page) => $page
        ->component('admin/roles/index')->has('roles.data', 10)->where('roles.total', 12)
        ->where('roles.next_page_url', fn ($url) => str_contains($url, 'search=Team') && str_contains($url, 'sort=name')));
    $this->get('/admin/roles?sort=guard_name')->assertSessionHasErrors('sort');
});
