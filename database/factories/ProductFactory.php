<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
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
        $model = 'iPhone '.fake()->numberBetween(15, 17).' '.fake()->randomElement(['', 'Plus', 'Pro', 'Pro Max']);
        $model = trim($model);
        $storage = fake()->randomElement(['128GB', '256GB', '512GB']);
        $color = fake()->randomElement(['Black', 'Blue', 'Silver', 'Natural Titanium']);

        return [
            'name' => "{$model} {$storage}",
            'sku' => Str::upper(Str::random(10)),
            'model' => $model,
            'description' => fake()->sentence(12),
            'series' => $model,
            'storage' => $storage,
            'color' => $color,
            'price' => fake()->numberBetween(79900, 179900),
            'stock' => fake()->numberBetween(0, 50),
            'image' => 'https://placehold.co/800x800/f5f5f7/1d1d1f?text='.urlencode($model),
            'is_featured' => false,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => ['is_featured' => true]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => ['stock' => 0]);
    }
}
