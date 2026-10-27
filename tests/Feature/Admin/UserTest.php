<?php

use App\Actions\Admin\ManageUserAccess;
use App\Actions\Admin\ManageUsers;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
    $this->data = ['name' => 'New teammate', 'username' => 'teammate', 'email' => 'teammate@example.com', 'password' => 'TestOnly!Password123', 'password_confirmation' => 'TestOnly!Password123'];
});

test('user CRUD endpoints require authentication and workspace access', function () {
    $target = User::factory()->create();
    $requests = [['get', '/admin/users'], ['get', '/admin/users/create'], ['get', "/admin/users/$target->id/edit"], ['post', '/admin/users'], ['put', "/admin/users/$target->id"], ['delete', "/admin/users/$target->id"]];
    foreach ($requests as [$method, $url]) {
        $this->{$method}($url)->assertRedirect(route('login'));
    }
    $user = User::factory()->create();
    $user->givePermissionTo(['users.view', 'users.create', 'users.update', 'users.delete']);
    $this->actingAs($user);
    foreach ($requests as [$method, $url]) {
        $this->{$method}($url, $this->data)->assertForbidden();
    }
    expect($target->fresh())->not->toBeNull();
});

test('each user action needs its own permission', function (string $method, string $path) {
    $target = User::factory()->create();
    $actor = User::factory()->create();
    $actor->givePermissionTo('admin.access');
    $this->actingAs($actor)->{$method}(str_replace('{id}', (string) $target->id, $path), $this->data)->assertForbidden();
    expect($target->fresh()->name)->toBe($target->name);
})->with([['get', '/admin/users'], ['get', '/admin/users/create'], ['get', '/admin/users/{id}/edit'], ['post', '/admin/users'], ['put', '/admin/users/{id}'], ['delete', '/admin/users/{id}']]);

test('authorized user CRUD hashes passwords and manages role assignments', function () {
    $role = Role::create(['name' => 'Viewer', 'guard_name' => 'web']);
    $role->givePermissionTo(['admin.access', 'dashboard.view']);
    $this->actingAs($this->admin)->get('/admin/users/create')->assertOk();
    $this->post('/admin/users', [...$this->data, 'roles' => [$role->id]])->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
    $target = User::where('email', $this->data['email'])->firstOrFail();
    expect(Hash::check($this->data['password'], $target->password))->toBeTrue()->and($target->hasRole('Viewer'))->toBeTrue();
    $this->get("/admin/users/$target->id/edit")->assertInertia(fn (Assert $page) => $page->component('admin/users/form')->where('user.email', $target->email)->where('canAssignRoles', true));
    $this->put("/admin/users/$target->id", ['name' => 'Renamed', 'email' => $target->email, 'roles' => []])->assertSessionHasNoErrors();
    expect($target->fresh()->name)->toBe('Renamed')->and($target->fresh()->roles)->toHaveCount(0);
    $this->delete("/admin/users/$target->id")->assertSessionHasNoErrors();
    expect($target->fresh())->toBeNull();
});

test('name email and initial password are validated', function (array $change, string $error) {
    $this->actingAs($this->admin)->post('/admin/users', [...$this->data, ...$change])->assertSessionHasErrors($error);
    expect(User::count())->toBe(1);
})->with([
    [['name' => ''], 'name'], [['name' => str_repeat('a', 256)], 'name'], [['email' => 'invalid'], 'email'],
    [['email' => ''], 'email'], [['password' => ''], 'password'], [['password_confirmation' => 'different'], 'password'],
]);

test('email validation ignores only the current user and the database enforces uniqueness', function () {
    $target = User::factory()->create();
    $this->actingAs($this->admin)->put("/admin/users/$target->id", ['name' => $target->name, 'email' => $target->email])->assertSessionHasNoErrors();
    $this->post('/admin/users', [...$this->data, 'email' => $target->email])->assertSessionHasErrors('email');
    $this->put("/admin/users/$target->id", ['name' => 'Duplicate', 'email' => $this->admin->email])->assertSessionHasErrors('email');
    expect(fn () => User::factory()->create(['email' => $target->email]))->toThrow(UniqueConstraintViolationException::class);
});

test('email is normalized consistently with authentication and changes clear verification', function () {
    $this->actingAs($this->admin)->post('/admin/users', [...$this->data, 'email' => '  TEAMMATE@EXAMPLE.COM  '])->assertSessionHasNoErrors();
    $target = User::where('email', 'teammate@example.com')->firstOrFail();
    $target->email_verified_at = now();
    $target->save();
    $this->put("/admin/users/$target->id", ['name' => 'Updated', 'email' => $target->email])->assertSessionHasNoErrors();
    expect($target->fresh()->email_verified_at)->not->toBeNull();
    $this->put("/admin/users/$target->id", ['name' => 'Updated', 'email' => 'CHANGED@EXAMPLE.COM'])->assertSessionHasNoErrors();
    expect($target->fresh()->email)->toBe('changed@example.com')->and($target->fresh()->email_verified_at)->toBeNull();
});

test('blank or omitted passwords preserve the hash and supplied passwords are confirmed and hashed', function () {
    $target = User::factory()->create();
    $oldHash = $target->password;
    $data = ['name' => $target->name, 'email' => $target->email];
    $this->actingAs($this->admin)->put("/admin/users/$target->id", $data)->assertSessionHasNoErrors();
    $this->put("/admin/users/$target->id", [...$data, 'password' => '', 'password_confirmation' => ''])->assertSessionHasNoErrors();
    expect($target->fresh()->password)->toBe($oldHash);
    $this->put("/admin/users/$target->id", [...$data, 'password' => 'Updated!Password123', 'password_confirmation' => 'wrong'])->assertSessionHasErrors('password');
    expect($target->fresh()->password)->toBe($oldHash);
    $this->put("/admin/users/$target->id", [...$data, 'password' => 'Updated!Password123', 'password_confirmation' => 'Updated!Password123'])->assertSessionHasNoErrors();
    expect(Hash::check('Updated!Password123', $target->fresh()->password))->toBeTrue()->and(Hash::check('password', $target->fresh()->password))->toBeFalse();
});

test('role assignments require roles update including empty array removal attempts', function (array $roles) {
    $actor = User::factory()->create();
    $actor->givePermissionTo(['admin.access', 'users.create', 'users.update']);
    $target = User::factory()->create();
    $this->actingAs($actor)->post('/admin/users', [...$this->data, 'roles' => $roles])->assertSessionHasErrors('roles');
    $this->put("/admin/users/$target->id", ['name' => 'Forged', 'email' => $target->email, 'roles' => $roles])->assertSessionHasErrors('roles');
    expect($target->fresh()->name)->toBe($target->name)->and($target->fresh()->roles)->toHaveCount(0);
})->with([[[]], [[999999]]]);

test('ordinary account editing preserves roles without granting role management', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo(['admin.access', 'users.update']);
    $role = Role::create(['name' => 'No privileges', 'guard_name' => 'web']);
    $target = User::factory()->create();
    $target->assignRole($role);
    $this->actingAs($actor)->get("/admin/users/$target->id/edit")->assertInertia(fn (Assert $page) => $page->where('canAssignRoles', false)->has('roles', 0)->where('user.roles.0.name', 'No privileges'));
    $this->put("/admin/users/$target->id", ['name' => 'Allowed', 'email' => $target->email])->assertSessionHasNoErrors();
    expect($target->fresh()->hasRole($role))->toBeTrue();
});

test('unknown foreign guard and duplicate role assignments are rejected', function () {
    $foreign = Role::create(['name' => 'Foreign', 'guard_name' => 'api']);
    $admin = Role::findByName('Admin');
    $this->actingAs($this->admin)->post('/admin/users', [...$this->data, 'roles' => [999999]])->assertSessionHasErrors('roles.0');
    $this->post('/admin/users', [...$this->data, 'roles' => [$foreign->id]])->assertSessionHasErrors('roles.0');
    $this->post('/admin/users', [...$this->data, 'roles' => [$admin->id, $admin->id]])->assertSessionHasErrors('roles.0');
    expect(User::count())->toBe(1);
});

test('unauthorized role changes roll back account and password mutations', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo(['admin.access', 'users.create', 'users.update', 'roles.update']);
    $target = User::factory()->create();
    $oldHash = $target->password;
    $privileged = Role::create(['name' => 'Privileged', 'guard_name' => 'web']);
    $privileged->givePermissionTo('users.delete');
    $this->actingAs($actor)->put("/admin/users/$target->id", [...$this->data, 'roles' => [$privileged->id]])->assertForbidden();
    expect($target->fresh()->name)->toBe($target->name)->and($target->fresh()->password)->toBe($oldHash)->and($target->fresh()->roles)->toHaveCount(0);
    $this->post('/admin/users', [...$this->data, 'roles' => [Role::findByName('Admin')->id]])->assertForbidden();
    expect(User::where('email', $this->data['email'])->exists())->toBeFalse();
});

test('higher privilege credentials cannot be taken over through user editing', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo(['admin.access', 'users.update', 'users.delete']);
    $privileged = User::factory()->create();
    $privileged->givePermissionTo('roles.update');
    $this->actingAs($actor)->get('/admin/users/'.$this->admin->id.'/edit')->assertForbidden();
    $this->put('/admin/users/'.$this->admin->id, $this->data)->assertForbidden();
    $this->delete('/admin/users/'.$this->admin->id)->assertForbidden();
    $this->put("/admin/users/$privileged->id", $this->data)->assertForbidden();
    expect($this->admin->fresh()->email)->toBe($this->admin->email)->and($privileged->fresh()->email)->toBe($privileged->email);
});

test('Admin membership never bypasses the action permission checks', function () {
    $target = User::factory()->create();
    Role::findByName('Admin')->revokePermissionTo('users.update');
    $this->admin->unsetRelation('roles')->unsetRelation('permissions');
    $this->actingAs($this->admin)->put("/admin/users/$target->id", $this->data)->assertForbidden();
});

test('self deletion is prevented in user management even when another Admin exists', function () {
    User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin)->delete('/admin/users/'.$this->admin->id)->assertForbidden();
    expect($this->admin->fresh())->not->toBeNull();
    $this->assertAuthenticated();
    $this->get('/admin/users?search='.$this->admin->email)->assertInertia(fn (Assert $page) => $page->where('users.data.0.is_self', true)->where('users.data.0.can.delete', false));
});

test('final Admin role cannot be removed by an account update and all fields roll back', function () {
    $oldHash = $this->admin->password;
    $this->actingAs($this->admin)->put('/admin/users/'.$this->admin->id, [...$this->data, 'roles' => []])->assertSessionHasErrors('roles');
    expect($this->admin->fresh()->hasRole('Admin'))->toBeTrue()->and($this->admin->fresh()->email)->toBe($this->admin->email)->and($this->admin->fresh()->password)->toBe($oldHash);
    expect(fn () => app(ManageUserAccess::class)->deleteAccount($this->admin))->toThrow(ValidationException::class);
});

test('another administrator can be deleted while the final administrator remains protected', function () {
    $other = User::factory()->create();
    $other->assignRole('Admin');
    $this->actingAs($this->admin)->delete("/admin/users/$other->id")->assertSessionHasNoErrors();
    expect($other->fresh())->toBeNull()->and(User::role('Admin')->count())->toBe(1);
});

test('self demotion returns to an accessible page without altering login state', function () {
    User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin)->put('/admin/users/'.$this->admin->id, ['name' => $this->admin->name, 'email' => $this->admin->email, 'roles' => []])
        ->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));
    $this->assertAuthenticated();
    expect($this->admin->fresh()->hasRole('Admin'))->toBeFalse();
});

test('user search role filtering and pagination preserve query parameters', function () {
    $role = Role::create(['name' => 'Team', 'guard_name' => 'web']);
    foreach (range(1, 12) as $i) {
        User::factory()->create(['name' => sprintf('Teammate %02d', $i), 'email' => sprintf('team%02d@example.com', $i)])->assignRole($role);
    }
    User::factory()->create(['name' => 'Teammate elsewhere']);
    $url = '/admin/users?search=Teammate&role_id='.$role->id.'&sort=email&direction=asc';
    $this->actingAs($this->admin)->get($url)->assertInertia(fn (Assert $page) => $page->component('admin/users/index')->has('users.data', 10)->where('users.total', 12)
        ->where('users.data.0.email', 'team01@example.com')->where('users.data.0.roles.0.name', 'Team')
        ->where('users.next_page_url', fn ($url) => str_contains($url, 'search=Teammate') && str_contains($url, 'role_id='.$role->id) && str_contains($url, 'sort=email')));
    $this->get($url.'&page=2')->assertInertia(fn (Assert $page) => $page->has('users.data', 2));
    $this->get('/admin/users?search=team01@example.com')->assertInertia(fn (Assert $page) => $page->where('users.total', 1));
    $this->get('/admin/users?search=missing-user')->assertInertia(fn (Assert $page) => $page->has('users.data', 0));
});

test('user filters reject unsafe input', function (string $query, string $error) {
    $this->actingAs($this->admin)->get('/admin/users?'.$query)->assertSessionHasErrors($error);
})->with([['sort=password', 'sort'], ['role_id=999999', 'role_id'], ['page=0', 'page'], ['direction=invalid', 'direction'], ['search[]=invalid', 'search']]);

test('user responses never serialize password hashes tokens secrets or permission relationships', function () {
    $this->actingAs($this->admin)->get('/admin/users')->assertInertia(fn (Assert $page) => $page
        ->missing('users.data.0.password')->missing('users.data.0.remember_token')->missing('users.data.0.two_factor_secret')
        ->missing('users.data.0.roles.0.permissions')->missing('users.data.0.permissions'));
    $this->get('/admin/users/'.$this->admin->id.'/edit')->assertInertia(fn (Assert $page) => $page
        ->missing('user.password')->missing('user.remember_token')->missing('user.two_factor_recovery_codes')->missing('user.roles.0.permissions'));
});

test('direct permissions and authentication internals cannot be supplied through user writes', function () {
    $this->actingAs($this->admin)->post('/admin/users', [...$this->data, 'permissions' => ['admin.access'], 'email_verified_at' => now()->toDateString(), 'remember_token' => 'forged'])
        ->assertSessionHasErrors(['permissions', 'email_verified_at', 'remember_token']);
    expect(User::count())->toBe(1);
});

test('deleting accounts removes related database sessions reset tokens and roles', function () {
    $target = User::factory()->create();
    $target->assignRole(Role::create(['name' => 'Empty', 'guard_name' => 'web']));
    DB::table('sessions')->insert(['id' => 'target-session', 'user_id' => $target->id, 'payload' => '', 'last_activity' => time()]);
    DB::table('password_reset_tokens')->insert(['email' => $target->email, 'token' => 'old-token', 'created_at' => now()]);
    $this->actingAs($this->admin)->delete("/admin/users/$target->id")->assertSessionHasNoErrors();
    expect(DB::table('sessions')->where('user_id', $target->id)->exists())->toBeFalse()
        ->and(DB::table('password_reset_tokens')->where('email', $target->email)->exists())->toBeFalse()
        ->and(DB::table('model_has_roles')->where('model_id', $target->id)->exists())->toBeFalse();
});

test('password updates invalidate outstanding reset tokens and rotate remember tokens', function () {
    $target = User::factory()->create(['remember_token' => 'old-remember-token']);
    DB::table('password_reset_tokens')->insert(['email' => $target->email, 'token' => 'old-reset-token', 'created_at' => now()]);
    $this->actingAs($this->admin)->put("/admin/users/$target->id", [...$this->data, 'email' => $target->email])->assertSessionHasNoErrors();
    expect($target->fresh()->remember_token)->not->toBe('old-remember-token')
        ->and(DB::table('password_reset_tokens')->where('email', $target->email)->exists())->toBeFalse();
});

test('saved accounts can use the existing Fortify login behavior', function () {
    $this->actingAs($this->admin)->post('/admin/users', [...$this->data, 'roles' => [Role::findByName('Admin')->id]])->assertSessionHasNoErrors();
    $this->post(route('logout'));
    $this->post(route('login.store'), ['username' => $this->data['username'], 'password' => $this->data['password']])->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs(User::where('email', $this->data['email'])->firstOrFail());
    $this->get(route('dashboard'))->assertOk();
});

test('write actions reauthorize revoked permissions under the shared lock', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo(['admin.access', 'users.update']);
    $target = User::factory()->create();
    expect($actor->can('users.update'))->toBeTrue();
    DB::table('model_has_permissions')->where('model_id', $actor->id)->where('permission_id', Permission::findByName('users.update')->id)->delete();
    expect(fn () => app(ManageUsers::class)->save($actor, $target, ['name' => 'Forbidden', 'email' => $target->email, 'password' => null], null))->toThrow(AuthorizationException::class);
    expect($target->fresh()->name)->toBe($target->name);
});

test('missing users return not found', function () {
    $this->actingAs($this->admin)->get('/admin/users/999999/edit')->assertNotFound();
    $this->put('/admin/users/999999', $this->data)->assertNotFound();
    $this->delete('/admin/users/999999')->assertNotFound();
});
