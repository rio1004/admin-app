<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ManageCategories;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'in:name,slug,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $categories = Category::query()->select(['id', 'name', 'slug', 'created_at'])
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($query) use ($filters) {
                $query->where(fn ($query) => $query->where('name', 'like', '%'.$filters['search'].'%')->orWhere('slug', 'like', '%'.$filters['search'].'%'));
            })
            ->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')->orderBy('id')
            ->paginate(10)->withQueryString();
        $categories->through(fn (Category $category) => [
            'id' => $category->id, 'name' => $category->name, 'slug' => $category->slug,
            'can' => ['update' => $request->user()->can('update', $category), 'delete' => $request->user()->can('delete', $category)],
        ]);

        return Inertia::render('admin/categories/index', [
            'categories' => $categories, 'filters' => $filters,
            'canCreate' => $request->user()->can('create', Category::class),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form($request);
    }

    public function edit(Request $request, Category $category): Response
    {
        return $this->form($request, $category);
    }

    private function form(Request $request, ?Category $category = null): Response
    {
        return Inertia::render('admin/categories/form', [
            'category' => $category?->only(['id', 'name', 'slug']),
            'canViewCategories' => $request->user()->can('viewAny', Category::class),
        ]);
    }

    public function store(CategoryRequest $request, ManageCategories $categories): RedirectResponse
    {
        $category = $categories->create($request->validated('name'));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category created.']);

        if ($request->user()->can('viewAny', Category::class)) {
            return to_route('admin.categories.index');
        }

        return $request->user()->can('update', $category) ? to_route('admin.categories.edit', $category) : to_route('admin.categories.create');
    }

    public function update(CategoryRequest $request, Category $category, ManageCategories $categories): RedirectResponse
    {
        $categories->update($category, $request->validated('name'));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category updated.']);

        return to_route('admin.categories.index', $category);
    }

    public function destroy(Request $request, Category $category, ManageCategories $categories): RedirectResponse
    {
        $categories->delete($category);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category deleted.']);

        return $request->user()->can('viewAny', Category::class) ? back() : to_route('profile.edit');
    }
}
