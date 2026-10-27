<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Item> */
class ItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['name' => fake()->words(3, true), 'category_id' => Category::factory(),
            'slug' => fake()->unique()->slug(), 'image' => null, 'link' => null];
    }
}
