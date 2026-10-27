<?php

use App\Actions\Admin\CategoryItemUsage;
use App\Actions\Admin\ManageItems;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
    $this->category = Category::factory()->create();
    $this->data = ['name' => 'Studio notebook', 'category_id' => $this->category->id, 'link' => null];
});

test('all item endpoints require authentication and workspace access', function () {
    $item = Item::factory()->create();
    $requests = [['get', '/admin/items'], ['get', '/admin/items/create'], ['get', "/admin/items/$item->id"], ['get', "/admin/items/$item->id/edit"], ['post', '/admin/items'], ['put', "/admin/items/$item->id"], ['delete', "/admin/items/$item->id"]];
    foreach ($requests as [$method, $url]) {
        $this->{$method}($url)->assertRedirect(route('login'));
    }
    $user = User::factory()->create();
    $user->givePermissionTo(['items.view', 'items.create', 'items.update', 'items.delete']);
    $this->actingAs($user);
    foreach ($requests as [$method, $url]) {
        $this->{$method}($url, $this->data)->assertForbidden();
    }
    expect(Item::count())->toBe(1);
});

test('item endpoints require their own action permission', function (string $method, string $path) {
    $item = Item::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo('admin.access');
    $this->actingAs($user)->{$method}(str_replace('{id}', (string) $item->id, $path), $this->data)->assertForbidden();
    expect(Item::count())->toBe(1)->and($item->fresh()->name)->toBe($item->name);
})->with([
    ['get', '/admin/items'], ['get', '/admin/items/create'], ['get', '/admin/items/{id}'], ['get', '/admin/items/{id}/edit'],
    ['post', '/admin/items'], ['put', '/admin/items/{id}'], ['delete', '/admin/items/{id}'],
]);

test('authorized item CRUD supports category reassignment stable slugs and nullable fields', function () {
    $this->actingAs($this->admin)->get('/admin/items/create')->assertOk();
    $this->post('/admin/items', $this->data)->assertSessionHasNoErrors();
    $item = Item::query()->sole();
    expect($item->slug)->toBe('studio-notebook')->and($item->image)->toBeNull()->and($item->link)->toBeNull();
    $this->get("/admin/items/$item->id")->assertInertia(fn (Assert $page) => $page->component('admin/items/show')
        ->where('item.category.id', $this->category->id)->where('item.image_url', null)->missing('item.image'));
    $this->get("/admin/items/$item->id/edit")->assertOk();
    $other = Category::factory()->create();
    $this->put("/admin/items/$item->id", ['name' => 'Renamed item', 'category_id' => $other->id, 'link' => 'https://example.com/item'])
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.items.show', $item));
    expect($item->fresh()->slug)->toBe('studio-notebook')->and($item->fresh()->category_id)->toBe($other->id);
    $this->put("/admin/items/$item->id", [...$this->data, 'link' => ''])->assertSessionHasNoErrors();
    expect($item->fresh()->link)->toBeNull();
    $this->delete("/admin/items/$item->id")->assertSessionHasNoErrors()->assertRedirect(route('admin.items.index'));
    expect($item->fresh())->toBeNull();
});

test('item descriptions can be created displayed updated and cleared', function () {
    $description = "First line\n<script>alert('plain text')</script>";
    $this->actingAs($this->admin)->post('/admin/items', [...$this->data, 'description' => $description])->assertSessionHasNoErrors();
    $item = Item::query()->sole();
    expect($item->description)->toBe($description);
    foreach (["/admin/items/$item->id", "/admin/items/$item->id/edit"] as $url) {
        $this->get($url)->assertInertia(fn (Assert $page) => $page->where('item.description', $description));
    }
    $this->get('/admin/items')->assertInertia(fn (Assert $page) => $page->where('items.data.0.description', $description));
    $this->put("/admin/items/$item->id", [...$this->data, 'description' => str_repeat('a', 5000)])->assertSessionHasNoErrors();
    expect($item->fresh()->description)->toHaveLength(5000);
    $this->put("/admin/items/$item->id", [...$this->data, 'description' => ''])->assertSessionHasNoErrors();
    expect($item->fresh()->description)->toBeNull();
});

test('invalid descriptions cannot be created or saved', function (mixed $description) {
    $item = Item::factory()->create(['description' => 'Keep this description']);
    $this->actingAs($this->admin)->post('/admin/items', [...$this->data, 'description' => $description])->assertSessionHasErrors('description');
    $this->put("/admin/items/$item->id", [...$this->data, 'description' => $description])->assertSessionHasErrors('description');
    expect(Item::count())->toBe(1)->and($item->fresh()->description)->toBe('Keep this description');
})->with([[['invalid']], [str_repeat('x', 5001)]]);

test('users without item update access cannot change descriptions', function () {
    $item = Item::factory()->create(['description' => 'Original description']);
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'items.view']);
    $this->actingAs($user)->put("/admin/items/$item->id", [...$this->data, 'description' => 'Unauthorized edit'])->assertForbidden();
    expect($item->fresh()->description)->toBe('Original description');
});

test('item values are optional strings that can be created viewed updated and cleared', function () {
    $this->actingAs($this->admin)->post('/admin/items', [...$this->data, 'value' => '00123 / Large'])->assertSessionHasNoErrors();
    $item = Item::query()->sole();
    expect($item->value)->toBe('00123 / Large');
    foreach (["/admin/items/$item->id", "/admin/items/$item->id/edit"] as $url) {
        $this->get($url)->assertInertia(fn (Assert $page) => $page->where('item.value', '00123 / Large'));
    }
    $this->get('/admin/items')->assertInertia(fn (Assert $page) => $page->where('items.data.0.value', '00123 / Large'));
    foreach (['0', str_repeat('x', 255), ''] as $value) {
        $this->put("/admin/items/$item->id", [...$this->data, 'value' => $value])->assertSessionHasNoErrors();
        expect($item->fresh()->value)->toBe($value === '' ? null : $value);
    }
    $this->post('/admin/items', $this->data)->assertSessionHasNoErrors();
    expect(Item::query()->latest('id')->first()->value)->toBeNull();
});

test('invalid item values are rejected on creation and update', function (mixed $value) {
    $item = Item::factory()->create(['value' => 'Keep this']);
    $this->actingAs($this->admin)->post('/admin/items', [...$this->data, 'value' => $value])->assertSessionHasErrors('value');
    $this->put("/admin/items/$item->id", [...$this->data, 'value' => $value])->assertSessionHasErrors('value');
    expect(Item::count())->toBe(1)->and($item->fresh()->value)->toBe('Keep this');
})->with([[['invalid']], [str_repeat('x', 256)], [123]]);

test('users without update permission cannot change item values', function () {
    $item = Item::factory()->create(['value' => 'Original']);
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'items.view']);
    $this->actingAs($user)->put("/admin/items/$item->id", [...$this->data, 'value' => 'Changed'])->assertForbidden();
    expect($item->fresh()->value)->toBe('Original');
});

test('item names and category selection are validated', function (array $change, string $error) {
    $this->actingAs($this->admin)->post('/admin/items', [...$this->data, ...$change])->assertSessionHasErrors($error);
    expect(Item::count())->toBe(0);
})->with([
    [['name' => ''], 'name'], [['name' => str_repeat('x', 121)], 'name'], [['name' => ['invalid']], 'name'],
    [['category_id' => null], 'category_id'], [['category_id' => 999999], 'category_id'], [['category_id' => 'invalid'], 'category_id'],
]);

test('links reject non web protocols and overlong values', function (string $link) {
    $this->actingAs($this->admin)->post('/admin/items', [...$this->data, 'link' => $link])->assertSessionHasErrors('link');
})->with(['javascript:alert(1)', 'data:text/html,hello', 'ftp://example.com/file', '//example.com', 'https://example.com/'.str_repeat('a', 2048)]);

test('http and https links are accepted', function (string $link) {
    $this->actingAs($this->admin)->post('/admin/items', [...$this->data, 'link' => $link])->assertSessionHasNoErrors();
    expect(Item::query()->sole()->link)->toBe($link);
})->with(['http://example.com', 'https://example.com/items?q=1']);

test('item slugs are collision safe stable and backed by a unique index', function () {
    $this->actingAs($this->admin);
    foreach (['Café', 'Cafe', '!!!', '???'] as $name) {
        $this->post('/admin/items', [...$this->data, 'name' => $name])->assertSessionHasNoErrors();
    }
    expect(Item::orderBy('id')->pluck('slug')->all())->toBe(['cafe', 'cafe-2', 'item', 'item-2']);
    $item = Item::firstOrFail();
    $this->put("/admin/items/$item->id", [...$this->data, 'slug' => 'forged'])->assertSessionHasErrors('slug');
    expect($item->fresh()->slug)->toBe('cafe');
    expect(fn () => Item::factory()->create(['slug' => 'cafe']))->toThrow(UniqueConstraintViolationException::class);
});

test('item listing combines search category filtering sorting and pagination', function () {
    foreach (range(1, 12) as $i) {
        Item::factory()->create(['category_id' => $this->category->id, 'name' => sprintf('Team %02d', $i), 'slug' => sprintf('team-%02d', $i)]);
    }
    Item::factory()->create(['name' => 'Team elsewhere']);
    $url = '/admin/items?search=Team&category_id='.$this->category->id.'&sort=name&direction=asc';
    $this->actingAs($this->admin)->get($url)->assertInertia(fn (Assert $page) => $page->component('admin/items/index')
        ->has('items.data', 10)->where('items.total', 12)->where('items.data.0.name', 'Team 01')
        ->where('items.data.0.category.name', $this->category->name)
        ->where('items.next_page_url', fn ($url) => str_contains($url, 'search=Team') && str_contains($url, 'category_id='.$this->category->id) && str_contains($url, 'sort=name')));
    $this->get($url.'&page=2')->assertInertia(fn (Assert $page) => $page->has('items.data', 2));
    $this->get('/admin/items?search=team-01')->assertInertia(fn (Assert $page) => $page->where('items.total', 1));
    $this->get('/admin/items?search=0')->assertInertia(fn (Assert $page) => $page->where('items.total', 10));
    $this->get('/admin/items?search=absent')->assertInertia(fn (Assert $page) => $page->has('items.data', 0));
});

test('invalid item filters are rejected', function (string $query, string $error) {
    $this->actingAs($this->admin)->get('/admin/items?'.$query)->assertSessionHasErrors($error);
})->with([['sort=password', 'sort'], ['direction=unsafe', 'direction'], ['page=0', 'page'], ['category_id=999999', 'category_id'], ['search[]=invalid', 'search']]);

test('read only users can view details but receive no mutation capabilities', function () {
    $item = Item::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'items.view']);
    $this->actingAs($user)->get('/admin/items')->assertInertia(fn (Assert $page) => $page->where('canCreate', false)->where('items.data.0.can.update', false)->where('items.data.0.can.delete', false));
    $this->get("/admin/items/$item->id")->assertOk();
});

test('item creation can select categories without category management permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.access', 'items.create']);
    $this->actingAs($user)->get('/admin/items/create')->assertInertia(fn (Assert $page) => $page->where('categories.0.id', $this->category->id)->where('canCreateCategory', false));
    $this->post('/admin/items', $this->data)->assertSessionHasNoErrors()->assertRedirect(route('admin.items.create'));
});

test('image uploads use generated paths and expose separate urls', function () {
    $upload = UploadedFile::fake()->image('client-name.jpg');
    $this->actingAs($this->admin)->post('/admin/items', [...$this->data, 'image' => $upload])->assertSessionHasNoErrors();
    $item = Item::query()->sole();
    expect($item->image)->toStartWith('items/')->not->toContain('client-name');
    Storage::disk('public')->assertExists($item->image);
    $this->get("/admin/items/$item->id")->assertInertia(fn (Assert $page) => $page->where('item.image_url', Storage::disk('public')->url($item->image))->missing('item.image'));
});

test('image validation rejects disguised content svg unsupported types size and dimensions', function (string $kind) {
    $upload = match ($kind) {
        'disguised' => UploadedFile::fake()->createWithContent('payload.jpg', '<?php echo "bad";'),
        'svg' => UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
        'gif' => UploadedFile::fake()->image('image.gif'),
        'size' => UploadedFile::fake()->image('image.png')->size(5121),
        'dimensions' => UploadedFile::fake()->image('image.png', 4097, 1),
    };
    $this->actingAs($this->admin)->post('/admin/items', [...$this->data, 'image' => $upload])->assertSessionHasErrors('image');
    expect(Storage::disk('public')->allFiles())->toBe([])->and(Item::count())->toBe(0);
})->with(['disguised', 'svg', 'gif', 'size', 'dimensions']);

test('image updates distinguish keep replace and explicit removal', function () {
    Storage::disk('public')->put('items/old.png', 'old');
    $item = Item::factory()->create(['image' => 'items/old.png']);
    $this->actingAs($this->admin)->put("/admin/items/$item->id", $this->data)->assertSessionHasNoErrors();
    expect($item->fresh()->image)->toBe('items/old.png');
    Storage::disk('public')->assertExists('items/old.png');
    $this->post("/admin/items/$item->id", [...$this->data, '_method' => 'put', 'image' => UploadedFile::fake()->image('new.png')])->assertSessionHasNoErrors();
    $newPath = $item->fresh()->image;
    Storage::disk('public')->assertMissing('items/old.png');
    Storage::disk('public')->assertExists($newPath);
    $this->put("/admin/items/$item->id", [...$this->data, 'remove_image' => true])->assertSessionHasNoErrors();
    Storage::disk('public')->assertMissing($newPath);
    expect($item->fresh()->image)->toBeNull();
});

test('conflicting image intentions and client storage paths are rejected', function () {
    $item = Item::factory()->create(['image' => 'items/old.png']);
    Storage::disk('public')->put('items/old.png', 'old');
    Storage::disk('public')->put('unrelated.txt', 'untouched');
    $this->actingAs($this->admin)->post("/admin/items/$item->id", [...$this->data, '_method' => 'put', 'image' => UploadedFile::fake()->image('new.png'), 'remove_image' => true])->assertSessionHasErrors('image');
    $this->put("/admin/items/$item->id", [...$this->data, 'image' => 'unrelated.txt', 'image_path' => 'unrelated.txt'])->assertSessionHasErrors(['image', 'image_path']);
    Storage::disk('public')->assertExists(['items/old.png', 'unrelated.txt']);
    expect($item->fresh()->image)->toBe('items/old.png');
});

test('failed writes clean new images and preserve old images and database values', function () {
    $item = Item::factory()->create(['image' => 'items/old.png']);
    Storage::disk('public')->put('items/old.png', 'old');
    Event::listen('eloquent.saving: '.Item::class, fn () => throw new RuntimeException('Simulated database write failure'));
    $this->withoutExceptionHandling()->actingAs($this->admin);
    expect(fn () => $this->post("/admin/items/$item->id", [...$this->data, '_method' => 'put', 'image' => UploadedFile::fake()->image('new.png')]))->toThrow(RuntimeException::class);
    expect($item->fresh()->image)->toBe('items/old.png')->and($item->fresh()->name)->toBe($item->name);
    expect(Storage::disk('public')->allFiles('items'))->toBe(['items/old.png']);
});

test('failed creation and a category removed before saving clean the upload', function () {
    $this->category->delete();
    expect(fn () => app(ManageItems::class)->save(null, $this->data, UploadedFile::fake()->image('new.png'), false))->toThrow(ValidationException::class);
    expect(Item::count())->toBe(0)->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('outer transaction rollback restores old image reference and cleans new uploads', function () {
    $item = Item::factory()->create(['image' => 'items/old.png']);
    Storage::disk('public')->put('items/old.png', 'old');
    DB::beginTransaction();
    $updated = app(ManageItems::class)->save($item, $this->data, UploadedFile::fake()->image('new.png'), false);
    $newPath = $updated->image;
    Storage::disk('public')->assertExists(['items/old.png', $newPath]);
    DB::rollBack();
    Storage::disk('public')->assertMissing($newPath);
    Storage::disk('public')->assertExists('items/old.png');
    expect($item->fresh()->image)->toBe('items/old.png');
});

test('item deletion removes its image only after successful database deletion', function () {
    $item = Item::factory()->create(['image' => 'items/old.png']);
    Storage::disk('public')->put('items/old.png', 'old');
    $this->actingAs($this->admin)->delete("/admin/items/$item->id")->assertSessionHasNoErrors();
    Storage::disk('public')->assertMissing('items/old.png');
    expect($item->fresh())->toBeNull();
});

test('failed item deletion keeps the record and image', function () {
    $item = Item::factory()->create(['image' => 'items/old.png']);
    Storage::disk('public')->put('items/old.png', 'old');
    Event::listen('eloquent.deleting: '.Item::class, fn () => throw new RuntimeException('Simulated deletion failure'));
    $this->withoutExceptionHandling()->actingAs($this->admin);
    expect(fn () => $this->delete("/admin/items/$item->id"))->toThrow(RuntimeException::class);
    Storage::disk('public')->assertExists('items/old.png');
    expect($item->fresh())->not->toBeNull();
});

test('upload storage failures return validation errors without changing the item', function () {
    $item = Item::factory()->create(['image' => 'items/old.png']);
    $filesystem = Mockery::mock(FilesystemAdapter::class);
    $filesystem->shouldReceive('putFileAs')->once()->andReturnFalse();
    $filesystem->shouldReceive('delete')->once()->andReturnTrue();
    Storage::shouldReceive('disk')->with('public')->andReturn($filesystem);
    $this->actingAs($this->admin)->post("/admin/items/$item->id", [...$this->data, '_method' => 'put', 'image' => UploadedFile::fake()->image('new.png')])->assertSessionHasErrors('image');
    expect($item->fresh()->image)->toBe('items/old.png');
});

test('post commit cleanup failure is logged without reporting a failed save', function (bool $throws) {
    $item = Item::factory()->create(['image' => 'items/old.png']);
    $filesystem = Mockery::mock(FilesystemAdapter::class);
    $delete = $filesystem->shouldReceive('delete')->with('items/old.png')->once();
    if ($throws) {
        $delete->andThrow(new RuntimeException('Simulated storage outage'));
    } else {
        $delete->andReturnFalse();
    }
    Storage::shouldReceive('disk')->with('public')->andReturn($filesystem);
    Log::spy();
    $this->actingAs($this->admin)->put("/admin/items/$item->id", [...$this->data, 'remove_image' => true])->assertSessionHasNoErrors();
    expect($item->fresh()->image)->toBeNull();
    Log::shouldHaveReceived('warning')->once();
})->with([true, false]);

test('categories containing items are protected and can be deleted after reassignment', function () {
    $item = Item::factory()->create(['category_id' => $this->category->id]);
    $this->actingAs($this->admin)->delete('/admin/categories/'.$this->category->id)->assertSessionHasErrors('category');
    expect($this->category->fresh())->not->toBeNull()->and($item->fresh())->not->toBeNull();
    $other = Category::factory()->create();
    $this->put("/admin/items/$item->id", [...$this->data, 'category_id' => $other->id])->assertSessionHasNoErrors();
    $this->delete('/admin/categories/'.$this->category->id)->assertSessionHasNoErrors();
    expect($this->category->fresh())->toBeNull();
});

test('restrictive foreign key rejects direct category deletion and invalid item references', function () {
    Item::factory()->create(['category_id' => $this->category->id]);
    expect(fn () => DB::table('categories')->where('id', $this->category->id)->delete())->toThrow(QueryException::class);
    expect(fn () => Item::factory()->create(['category_id' => 999999]))->toThrow(QueryException::class);
});

test('foreign key deletion errors become actionable category validation errors', function () {
    Item::factory()->create(['category_id' => $this->category->id]);
    $this->mock(CategoryItemUsage::class, fn ($mock) => $mock->shouldReceive('hasItems')->once()->andReturnFalse());
    $this->actingAs($this->admin)->delete('/admin/categories/'.$this->category->id)->assertSessionHasErrors('category');
    expect($this->category->fresh())->not->toBeNull();
});

test('missing items return not found', function () {
    $this->actingAs($this->admin)->get('/admin/items/999999')->assertNotFound();
    $this->get('/admin/items/999999/edit')->assertNotFound();
    $this->put('/admin/items/999999', $this->data)->assertNotFound();
    $this->delete('/admin/items/999999')->assertNotFound();
});
