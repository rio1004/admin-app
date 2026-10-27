<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('admin.access') && $user->can('categories.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('admin.access') && $user->can('categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('admin.access') && $user->can('categories.update');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('admin.access') && $user->can('categories.delete');
    }
}
