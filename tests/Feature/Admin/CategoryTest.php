<?php

use App\Actions\Admin\CategoryItemUsage;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
});

test('every category endpoint requires authentication and admin access', function () {
    $category = Category::factory()->create();
    $requests = [['get', '/admin/categories'], ['get', '/admin/categories/create'], ['get', "/admin/categories/$category->id/edit"], ['post', '/admin/categories'], ['put', "/admin/categories/$category->id"], ['delete', "/admin/categories/$category->id"]];
    foreach ($requests as [$method, $url]) {
        $this->{$method}($url)->assertRedirect(route('login'));
    }
    $ordinary = User::factory()->create();
    $ordinary->givePermissionTo(['categories.view', 'categories.create', 'categories.update', 'categories.delete']);
    $this->actingAs($ordinary);
    foreach ($requests as [$method, $url]) {
        $this->{$method}($url)->assertForbidden();
    }
    expect(Category::count())->toBe(1);
});

test('category endpoints require the corresponding action permission', function (string $method, string $path) {
    $category = Category::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo('admin.access');
    $this->actingAs($user)->{$method}(str_replace('{id}', (string) $category->id, $path), ['name' => 'Forbidden'])->assertForbidden();
    expect($category->fresh()->name)->toBe($category->name)->and(Category::count())->toBe(1);
})->with([
    ['get', '/admin/categories'], ['get', '/admin/categories/create'], ['get', '/admin/categories/{id}/edit'],
    ['post', '/admin/categories'], ['put', '/admin/categories/{id}'], ['delete', '/admin/categories/{id}'],
]);

test('authorized category CRUD generates and preserves its slug', function () {
    $this->actingAs($this->admin)->get('/admin/categories/create')->assertOk();
    $this->post('/admin/categories', ['name' => 'Workspace Essentials'])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.index'));
    $category = Category::query()->sole();
    expect($category->slug)->toBe('workspace-essentials');
    $this->get("/admin/categories/$category->id/edit")->assertInertia(fn (Assert $page) => $page
        ->component('admin/categories/form')->where('category.name', 'Workspace Essentials')->where('category.slug', 'workspace-essentials'));
    $this->put("/admin/categories/$category->id", ['name' => 'Renamed collection'])->assertSessionHasNoErrors();
    $this->put("/admin/categories/$category->id", ['name' => 'Renamed collection'])->assertSessionHasNoErrors();
    expect($category->fresh()->name)->toBe('Renamed collection')->and($category->fresh()->slug)->toBe('workspace-essentials');
    $this->from('/admin/categories?search=Renamed&page=1')->delete("/admin/categories/$category->id")->assertSessionHasNoErrors()->assertRedirect('/admin/categories?search=Renamed&page=1');
    expect($category->fresh())->toBeNull();
});

test('names are required strings with a maximum of 120 characters', function (mixed $name) {
    $category = Category::factory()->create();
    $this->actingAs($this->admin)->post('/admin/categories', ['name' => $name])->assertSessionHasErrors('name');
    $this->put("/admin/categories/$category->id", ['name' => $name])->assertSessionHasErrors('name');
    expect(Category::count())->toBe(1)->and($category->fresh()->name)->toBe($category->name);
})->with([null, '', '   ', [['not a string']], str_repeat('x', 121)]);

test('duplicate and transliterated names get predictable unique slugs', function () {
    $this->actingAs($this->admin);
    foreach (['Café', 'Cafe', 'Cafe 2', 'Cafe'] as $name) {
        $this->post('/admin/categories', ['name' => $name])->assertSessionHasNoErrors();
    }
    expect(Category::orderBy('id')->pluck('slug')->all())->toBe(['cafe', 'cafe-2', 'cafe-2-2', 'cafe-3']);
});

test('empty generated slugs use a deterministic fallback', function () {
    $this->actingAs($this->admin);
    $this->post('/admin/categories', ['name' => '!!!'])->assertSessionHasNoErrors();
    $this->post('/admin/categories', ['name' => '???'])->assertSessionHasNoErrors();
    expect(Category::orderBy('id')->pluck('slug')->all())->toBe(['category', 'category-2']);
});

test('maximum length names and collisions fit the slug column', function () {
    $this->actingAs($this->admin);
    foreach (range(1, 2) as $i) {
        $this->post('/admin/categories', ['name' => str_repeat('a', 120)])->assertSessionHasNoErrors();
    }
    expect(Category::orderBy('id')->pluck('slug')->all())->toBe([str_repeat('a', 120), str_repeat('a', 120).'-2']);
});

test('clients cannot choose or overwrite generated slugs', function () {
    $category = Category::factory()->create();
    $this->actingAs($this->admin)->post('/admin/categories', ['name' => 'Forged', 'slug' => 'forged'])->assertSessionHasErrors('slug');
    $this->put("/admin/categories/$category->id", ['name' => 'Forged', 'slug' => 'forged'])->assertSessionHasErrors('slug');
    expect($category->fresh()->slug)->toBe($category->slug)->and($category->fresh()->name)->toBe($category->name)->and(Category::count())->toBe(1);
});

test('slug uniqueness is enforced by the database', function () {
    Category::factory()->create(['slug' => 'unique-slug']);
    expect(fn () => Category::factory()->create(['slug' => 'unique-slug']))->toThrow(UniqueConstraintViolationException::class);
});

test('category writes select only approved fields', function () {
    $this->actingAs($this->admin)->post('/admin/categories', ['id' => 99999, 'name' => 'Safe', 'created_at' => '2000-01-01', 'roles' => ['Admin']])->assertSessionHasNoErrors();
    $category = Category::query()->sole();
    expect($category->id)->not->toBe(99999)->and($category->created_at->year)->not->toBe(2000);
});

test('category search uses names and slugs and preserves sort through pagination', function () {
    foreach (range(1, 12) as $i) {
        Category::factory()->create(['name' => sprintf('Team %02d', $i), 'slug' => sprintf('team-%02d', $i)]);
    }
    Category::factory()->create(['name' => 'Other', 'slug' => 'other']);
    $this->actingAs($this->admin)->get('/admin/categories?search=Team&sort=name&direction=desc')->assertInertia(fn (Assert $page) => $page
        ->component('admin/categories/index')->has('categories.data', 10)->where('categories.total', 12)
        ->where('categories.data.0.name', 'Team 12')
        ->where('categories.next_page_url', fn ($url) => str_contains($url, 'search=Team') && str_contains($url, 'sort=name') && str_contains($url, 'direction=desc')));
    $this->get('/admin/categories?search=team-01')->assertInertia(fn (Assert $page) => $page->has('categories.data', 1)->where('categories.data.0.name', 'Team 01'));
    $this->get('/admin/categories?search=Team&sort=name&direction=desc&page=2')->assertInertia(fn (Assert $page) => $page->has('categories.data', 2)->where('categories.current_page', 2));
    $this->get('/admin/categories?search=0')->assertInertia(fn (Assert $page) => $page->where('categories.total', 10));
    $this->get('/admin/categories?search=missing')->assertInertia(fn (Assert $page) => $page->has('categories.data', 0));
});

test('category filtering rejects unsafe query parameters', function (string $query, string $error) {
    $this->actingAs($this->admin)->get('/admin/categories?'.$query)->assertSessionHasErrors($error);
})->with([['sort=password', 'sort'], ['direction=invalid', 'direction'], ['page=0', 'page'], ['search[]=array', 'search']]);

test('read only viewers receive no mutation capabilities', function () {
    Category::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'categories.view']);
    $this->actingAs($user)->get('/admin/categories')->assertInertia(fn (Assert $page) => $page
        ->where('canCreate', false)->where('categories.data.0.can.update', false)->where('categories.data.0.can.delete', false)
        ->where('auth.permissions', ['admin.access', 'categories.view']));
});

test('create only and update only users return to permitted screens', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'categories.create']);
    $this->actingAs($user)->post('/admin/categories', ['name' => 'Only create'])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.create'));
    $category = Category::query()->sole();
    $user->syncPermissions(['admin.access', 'categories.update']);
    $this->put("/admin/categories/$category->id", ['name' => 'Only edit'])->assertSessionHasNoErrors()->assertRedirect(route('admin.categories.edit', $category));
    $this->get("/admin/categories/$category->id/edit")->assertInertia(fn (Assert $page) => $page->where('canViewCategories', false));
});

test('category deletion rejects usage reported by the item integration', function () {
    $category = Category::factory()->create();
    $this->mock(CategoryItemUsage::class, function ($mock) use ($category) {
        $mock->shouldReceive('hasItems')->once()->withArgs(fn (Category $candidate) => $candidate->id === $category->id)->andReturn(true);
    });
    $this->actingAs($this->admin)->delete("/admin/categories/$category->id")->assertSessionHasErrors('category');
    expect($category->fresh())->not->toBeNull();
});

test('missing categories return not found to authorized users', function () {
    $this->actingAs($this->admin)->get('/admin/categories/999999/edit')->assertNotFound();
    $this->put('/admin/categories/999999', ['name' => 'Missing'])->assertNotFound();
    $this->delete('/admin/categories/999999')->assertNotFound();
});
