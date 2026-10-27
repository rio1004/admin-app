<?php

namespace App\Support;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

final class ItemData
{
    /** @return array<string, mixed> */
    public static function forUser(Item $item, User $user): array
    {
        return [
            'id' => $item->id, 'name' => $item->name, 'slug' => $item->slug,
            'description' => $item->description,
            'value' => $item->value,
            'category_id' => $item->category_id, 'category' => $item->category->only(['id', 'name']),
            'link' => $item->link, 'image_url' => $item->image ? Storage::disk('public')->url($item->image) : null,
            'can' => ['view' => $user->can('view', $item), 'update' => $user->can('update', $item), 'delete' => $user->can('delete', $item)],
        ];
    }
}
