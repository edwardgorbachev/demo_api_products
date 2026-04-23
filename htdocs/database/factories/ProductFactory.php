<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => fake()->words(fake()->numberBetween(2, 5), true),
            'description' => fake()->boolean(70) ? fake()->paragraph() : '',
            'price'       => fake()->randomFloat(2, 10, 50000),
            'category_id' => Category::inRandomOrder()->value('id'),
            'in_stock'    => fake()->boolean(20),
            'rating'      => fake()->randomFloat(1, 1, 5),
        ];
    }
}
