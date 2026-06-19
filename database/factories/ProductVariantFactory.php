<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $color = $this->faker->colorName();
        $size = $this->faker->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL']);

        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper($this->faker->lexify('???')) . '-' . $this->faker->unique()->numerify('###'),
            'price' => $this->faker->numberBetween(500, 5000),
            'offer_price' => $this->faker->optional()->randomFloat(2, 500, 5000),
            'stock' => $this->faker->numberBetween(0, 50),
        ];
    }
}
