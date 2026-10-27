<?php

namespace App\Actions\Admin;

use App\Models\Category;

class CategoryItemUsage
{
    public function hasItems(Category $category): bool
    {
        return $category->items()->exists();
    }
}
