<?php

namespace App\Actions\Admin;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ManageItems
{
    public function __construct(private ItemImages $images) {}

    /** @param array{name: string, description?: string|null, value?: string|null, category_id: int, link: string|null} $data */
    public function save(?Item $item, array $data, ?UploadedFile $upload, bool $removeImage): Item
    {
        $newPath = $upload ? $this->images->store($upload) : null;
        try {
            for ($attempt = 0; $attempt < 8; $attempt++) {
                try {
                    $saved = DB::transaction(function () use ($item, $data, $newPath, $removeImage) {
                        // Lock the category before the item. Category deletion uses
                        // the same parent row lock, while the FK is the final guard.
                        if (! Category::query()->whereKey($data['category_id'])->lockForUpdate()->first(['id'])) {
                            throw ValidationException::withMessages(['category_id' => 'This category no longer exists. Choose another category.']);
                        }
                        $record = $item ? Item::query()->whereKey($item->id)->lockForUpdate()->firstOrFail() : new Item;
                        $oldPath = $record->image;
                        $record->name = $data['name'];
                        if (array_key_exists('value', $data)) {
                            $record->value = $data['value'];
                        }
                        if (array_key_exists('description', $data)) {
                            $record->description = $data['description'];
                        }
                        $record->category_id = $data['category_id'];
                        $record->link = $data['link'];
                        if (! $item) {
                            $record->slug = $this->slug($data['name']);
                        }
                        if ($newPath !== null || $removeImage) {
                            $record->image = $newPath;
                        }
                        $record->save();
                        if ($oldPath !== null && $oldPath !== $record->image) {
                            DB::afterCommit(fn () => $this->images->delete($oldPath));
                        }

                        return $record;
                    }, 3);
                    if ($newPath !== null && DB::transactionLevel() > 0) {
                        DB::afterRollBack(fn () => $this->images->delete($newPath));
                    }

                    return $saved;
                } catch (UniqueConstraintViolationException $exception) {
                    if ($item) {
                        throw $exception;
                    }
                    if ($attempt === 7) {
                        throw ValidationException::withMessages(['name' => 'An item with this slug was just created. Please try again.']);
                    }
                }
            }
            throw new \LogicException('Item creation attempts exhausted.');
        } catch (Throwable $exception) {
            $this->images->delete($newPath);
            throw $exception;
        }
    }

    private function slug(string $name): string
    {
        $base = mb_substr(Str::slug($name), 0, 180) ?: 'item';
        $slug = $base;
        $suffix = 2;
        while (Item::query()->where('slug', $slug)->exists()) {
            if ($suffix > 10000) {
                throw ValidationException::withMessages(['name' => 'Please choose a more specific item name.']);
            }
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function delete(Item $item): void
    {
        DB::transaction(function () use ($item) {
            $record = Item::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $path = $record->image;
            $record->delete();
            DB::afterCommit(fn () => $this->images->delete($path));
        }, 3);
    }
}
