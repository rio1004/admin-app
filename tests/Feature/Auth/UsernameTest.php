<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('username migration preserves accounts and resolves duplicate and empty names', function () {
    User::factory()->create(['name' => 'Same Name']);
    User::factory()->create(['name' => 'Same Name']);
    User::factory()->create(['name' => '!!!']);
    $before = DB::table('users')->orderBy('id')->get(['id', 'email', 'password']);
    Schema::table('users', fn (Blueprint $table) => $table->dropUnique(['username']));
    Schema::table('users', fn (Blueprint $table) => $table->dropColumn('username'));
    (require database_path('migrations/2026_10_27_130000_add_username_to_users_table.php'))->up();
    expect(DB::table('users')->orderBy('id')->pluck('username')->all())->toBe(['same_name', 'same_name_2', 'user']);
    expect(DB::table('users')->orderBy('id')->get(['id', 'email', 'password'])->toJson())->toBe($before->toJson());
});

test('admin user forms normalize and validate unique usernames', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('Admin');
    $target = User::factory()->create();
    $data = ['name' => $target->name, 'email' => $target->email, 'username' => '  TEAM_MEMBER  '];
    $this->actingAs($admin)->put('/admin/users/'.$target->id, $data)->assertSessionHasNoErrors();
    expect($target->fresh()->username)->toBe('team_member');
    $this->put('/admin/users/'.$target->id, $data)->assertSessionHasNoErrors();
    $this->put('/admin/users/'.$target->id, [...$data, 'username' => $admin->username])->assertSessionHasErrors('username');
    $this->put('/admin/users/'.$target->id, [...$data, 'username' => 'invalid username'])->assertSessionHasErrors('username');
    expect(fn () => User::factory()->create(['username' => 'team_member']))->toThrow(UniqueConstraintViolationException::class);
});
