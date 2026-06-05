<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use App\Models\Unity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit_id' => Unity::factory()->create()->id,
            'quantity' => fake()->numberBetween(1, 10),
            'name' => fake()->name(),
            'sku' => fake()->numberBetween(1, 999999),
            'average_price' => fake()->randomFloat(2, 1, 1000),
            'category_id' => ProductCategory::factory()->create()->id,
            'ean' => fake()->numberBetween(1, 999999),
            'description' => fake()->text(),
        ];
    }
}
