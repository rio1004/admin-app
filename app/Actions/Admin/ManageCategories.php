<?php

namespace App\Actions\Admin;

use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ManageCategories
{
    public function __construct(private CategoryItemUsage $usage) {}

    public function create(string $name): Category
    {
        $base = mb_substr(Str::slug($name), 0, 180) ?: 'category';
        // Recalculate after a concurrent insert wins the same slug. Each failed
        // attempt rolls back before retrying; the unique index is authoritative.
        for ($attempt = 0; $attempt < 8; $attempt++) {
            try {
                return DB::transaction(function () use ($name, $base) {
                    $slug = $base;
                    $suffix = 2;
                    while (Category::query()->where('slug', $slug)->exists()) {
                        if ($suffix > 10000) {
                            throw ValidationException::withMessages(['name' => 'Please choose a more specific category name.']);
                        }
                        $slug = $base.'-'.$suffix++;
                    }
                    $category = new Category;
                    $category->name = $name;
                    $category->slug = $slug;
                    $category->save();

                    return $category;
                }, 3);
            } catch (UniqueConstraintViolationException) {
                if ($attempt === 7) {
                    throw ValidationException::withMessages(['name' => 'A category with this slug was just created. Please try again.']);
                }
            }
        }

        throw new \LogicException('Category creation attempts exhausted.');
    }

    public function update(Category $category, string $name): void
    {
        // Slugs are assigned on creation only, never regenerated on rename.
        $category->name = $name;
        $category->save();
    }

    public function delete(Category $category): void
    {
        try {
            DB::transaction(function () use ($category) {
                $category = Category::query()->whereKey($category->id)->lockForUpdate()->firstOrFail();
                if ($this->usage->hasItems($category)) {
                    throw ValidationException::withMessages(['category' => 'Categories containing items cannot be deleted. Reassign or delete their items first.']);
                }
                $category->delete();
            }, 3);
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1451 || str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed')) {
                throw ValidationException::withMessages(['category' => 'Categories containing items cannot be deleted. Reassign or delete their items first.']);
            }
            throw $exception;
        }
    }
}
