<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));
        $slug = Str::slug($name);

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => fake()->paragraph(),
            // Giá VNĐ làm tròn đến nghìn đồng
            'price' => fake()->numberBetween(20, 5000) * 1000,
            'stock' => fake()->numberBetween(0, 100),
            'image_url' => "https://picsum.photos/seed/{$slug}/600/600",
            'category_id' => Category::factory(),
        ];
    }
}
