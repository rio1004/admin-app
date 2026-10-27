<?php

namespace App\Http\Requests\Admin;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof Item ? ($this->user()?->can('update', $item) ?? false)
            : ($this->user()?->can('create', Item::class) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'value' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'link' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'image' => ['nullable', Rule::prohibitedIf($this->boolean('remove_image')), 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'remove_image' => ['sometimes', 'boolean'],
            'slug' => ['prohibited'],
            'image_path' => ['prohibited'],
        ];
    }
}
