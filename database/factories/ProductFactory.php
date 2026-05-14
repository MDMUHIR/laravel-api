<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\VariantImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->paragraph(),
            'short_description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(500, 5000),
            'offer_price' => $this->faker->optional()->randomFloat(2, 500, 5000),
            'stock' => $this->faker->numberBetween(0, 100),
            'has_variants' => false,
            'status' => true,
            'category_id' => Category::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            $product->update(['default_variant_id' => $product->id]);
        });
    }
}
