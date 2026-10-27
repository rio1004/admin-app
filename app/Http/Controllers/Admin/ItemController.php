<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ManageItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ItemRequest;
use App\Models\Category;
use App\Models\Item;
use App\Support\ItemData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'sort' => ['nullable', 'in:name,slug,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $items = Item::query()->with('category:id,name')
            ->when(isset($filters['search']) && $filters['search'] !== '', fn ($query) => $query
                ->where(fn ($query) => $query->where('name', 'like', '%'.$filters['search'].'%')->orWhere('slug', 'like', '%'.$filters['search'].'%')))
            ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')->orderBy('id')
            ->paginate(10)->withQueryString();
        $items->through(fn (Item $item) => $this->payload($request, $item));

        return Inertia::render('admin/items/index', ['items' => $items, 'filters' => $filters,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'canCreate' => $request->user()->can('create', Item::class)]);
    }

    public function create(Request $request): Response
    {
        return $this->form($request);
    }

    public function edit(Request $request, Item $item): Response
    {
        return $this->form($request, $item);
    }

    private function form(Request $request, ?Item $item = null): Response
    {
        $item?->load('category:id,name');

        return Inertia::render('admin/items/form', [
            'item' => $item ? $this->payload($request, $item) : null,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'canViewItems' => $request->user()->can('viewAny', Item::class),
            'canCreateCategory' => $request->user()->can('create', Category::class),
        ]);
    }

    public function show(Request $request, Item $item): Response
    {
        $item->load('category:id,name');

        return Inertia::render('admin/items/show', ['item' => $this->payload($request, $item)]);
    }

    /** @return array<string, mixed> */
    private function payload(Request $request, Item $item): array
    {
        return ItemData::forUser($item, $request->user());
    }

    public function store(ItemRequest $request, ManageItems $items): RedirectResponse
    {
        $item = $this->save($request, $items);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item created.']);

        return $this->destination($request, $item);
    }

    public function update(ItemRequest $request, Item $item, ManageItems $items): RedirectResponse
    {
        $item = $this->save($request, $items, $item);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item updated.']);

        return $this->destination($request, $item);
    }

    private function save(ItemRequest $request, ManageItems $items, ?Item $item = null): Item
    {
        $upload = $request->file('image');

        return $items->save($item, [
            'name' => $request->validated('name'), 'category_id' => (int) $request->validated('category_id'),
            'link' => $request->validated('link') ?? null,
        ], $upload instanceof UploadedFile ? $upload : null, $request->boolean('remove_image'));
    }

    private function destination(Request $request, Item $item): RedirectResponse
    {
        if ($request->user()->can('view', $item)) {
            return to_route('admin.items.show', $item);
        }
        if ($request->user()->can('update', $item)) {
            return to_route('admin.items.edit', $item);
        }

        return to_route('admin.items.create');
    }

    public function destroy(Request $request, Item $item, ManageItems $items): RedirectResponse
    {
        $items->delete($item);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Item deleted.']);

        return $request->user()->can('viewAny', Item::class) ? to_route('admin.items.index') : to_route('profile.edit');
    }
}
