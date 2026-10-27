<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authorized users can visit the dashboard', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'dashboard.view']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('ordinary authenticated users cannot visit the dashboard', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertForbidden();
});

test('dashboard totals use real records and recent items are bounded and newest first', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    User::factory()->count(2)->create();
    Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    Role::create(['name' => 'External guard', 'guard_name' => 'api']);
    $category = Category::factory()->create();
    Category::factory()->create();
    $created = Item::factory()->count(7)->create(['category_id' => $category->id, 'created_at' => now()->subDay()]);
    $latest = $created->last();
    $latest->image = 'items/example.png';
    $latest->save();
    $this->actingAs($admin)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')->where('counts.users', 3)->where('counts.roles', 2)->where('counts.items', 7)->where('counts.categories', 2)
        ->has('recentItems', 5)->where('recentItems.0.id', $latest->id)->where('recentItems.4.id', $created[2]->id)
        ->where('recentItems.0.category.name', $category->name)
        ->where('recentItems.0.image_url', Storage::disk('public')->url('items/example.png'))
        ->where('recentItems.0.can.update', true)->where('canCreateItem', true)
        ->missing('recentItems.0.image')->missing('recentItems.0.category.created_at'));
});

test('dashboard only exposes counts for permitted resources', function (string $resource) {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'dashboard.view', "$resource.view"]);
    $this->actingAs($user)->get('/dashboard')->assertInertia(function (Assert $page) use ($resource) {
        $page->component('dashboard')->has('counts', 1)->where("counts.$resource", match ($resource) {
            'users', 'roles' => 1, 'items', 'categories' => 0,
        })->where('canCreateItem', false);
        foreach (['users', 'roles', 'items', 'categories'] as $other) {
            if ($other !== $resource) {
                $page->missing("counts.$other");
            }
        }

        return $resource === 'items' ? $page->has('recentItems', 0) : $page->missing('recentItems');
    });
})->with(['users', 'roles', 'items', 'categories']);

test('dashboard without resource access omits both hidden data and its queries', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Item::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'dashboard.view']);
    $queries = [];
    DB::listen(function ($query) use (&$queries) {
        $queries[] = str_replace(['`', '"'], '', $query->sql);
    });
    $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page->has('counts', 0)->missing('recentItems')->where('canCreateItem', false));
    foreach ($queries as $query) {
        expect($query)->not->toContain('from items')->not->toContain('from categories')->not->toContain('count(*)');
    }
});

test('read only recent items expose no mutation capabilities', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $item = Item::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'dashboard.view', 'items.view']);
    $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('counts.items', 1)->where('recentItems.0.id', $item->id)
        ->where('recentItems.0.can.update', false)->where('recentItems.0.can.delete', false)->where('canCreateItem', false));
});

test('dashboard updates after records change rather than retaining preview totals', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'dashboard.view', 'items.view', 'items.create']);
    $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('counts.items', 0)->has('recentItems', 0)->where('canCreateItem', true));
    $item = Item::factory()->create();
    $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('counts.items', 1)->where('recentItems.0.id', $item->id));
    $item->delete();
    $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('counts.items', 0)->has('recentItems', 0));
});
