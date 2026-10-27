<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\Role;
use App\Models\User;
use App\Support\ItemData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $counts = [];
        if ($user->can('viewAny', User::class)) {
            $counts['users'] = User::query()->count();
        }
        if ($user->can('viewAny', Role::class)) {
            $counts['roles'] = Role::query()->where('guard_name', 'web')->count();
        }
        if ($user->can('viewAny', Item::class)) {
            $counts['items'] = Item::query()->count();
        }
        if ($user->can('viewAny', Category::class)) {
            $counts['categories'] = Category::query()->count();
        }
        $props = ['counts' => (object) $counts, 'canCreateItem' => $user->can('create', Item::class)];
        if ($user->can('viewAny', Item::class)) {
            $props['recentItems'] = Item::query()->with('category:id,name')
                ->latest('created_at')->orderByDesc('id')->limit(5)->get()
                ->map(fn (Item $item) => ItemData::forUser($item, $user));
        }

        return Inertia::render('dashboard', $props);
    }
}
