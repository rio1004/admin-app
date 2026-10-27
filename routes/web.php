<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Models\Category;
use App\Models\Item;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->middleware(['can:admin.access', 'can:dashboard.view'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('can:admin.access')->group(function () {
        Route::get('users', [UserController::class, 'index'])->can('viewAny', User::class)->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->can('create', User::class)->name('users.create');
        Route::post('users', [UserController::class, 'store'])->can('create', User::class)->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->can('update', 'user')->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->can('update', 'user')->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->can('delete', 'user')->name('users.destroy');
        Route::get('items', [ItemController::class, 'index'])->can('viewAny', Item::class)->name('items.index');
        Route::get('items/create', [ItemController::class, 'create'])->can('create', Item::class)->name('items.create');
        Route::post('items', [ItemController::class, 'store'])->can('create', Item::class)->name('items.store');
        Route::get('items/{item}', [ItemController::class, 'show'])->can('view', 'item')->name('items.show');
        Route::get('items/{item}/edit', [ItemController::class, 'edit'])->can('update', 'item')->name('items.edit');
        Route::put('items/{item}', [ItemController::class, 'update'])->can('update', 'item')->name('items.update');
        Route::delete('items/{item}', [ItemController::class, 'destroy'])->can('delete', 'item')->name('items.destroy');
        Route::get('categories', [CategoryController::class, 'index'])->can('viewAny', Category::class)->name('categories.index');
        Route::get('categories/create', [CategoryController::class, 'create'])->can('create', Category::class)->name('categories.create');
        Route::post('categories', [CategoryController::class, 'store'])->can('create', Category::class)->name('categories.store');
        Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])->can('update', 'category')->name('categories.edit');
        Route::put('categories/{category}', [CategoryController::class, 'update'])->can('update', 'category')->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->can('delete', 'category')->name('categories.destroy');
        Route::get('roles', [RoleController::class, 'index'])->can('viewAny', Role::class)->name('roles.index');
        Route::get('roles/create', [RoleController::class, 'create'])->can('create', Role::class)->name('roles.create');
        Route::post('roles', [RoleController::class, 'store'])->can('create', Role::class)->name('roles.store');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->can('update', 'role')->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->can('update', 'role')->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->can('delete', 'role')->name('roles.destroy');
        Route::put('users/{user}/roles', UserRoleController::class)->middleware('can:roles.update')->name('users.roles.update');
    });
});

require __DIR__.'/settings.php';
