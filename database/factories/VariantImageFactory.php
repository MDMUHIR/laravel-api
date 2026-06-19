<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\VariantImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class VariantImageFactory extends Factory
{
    protected $model = VariantImage::class;

    public function definition(): array
    {
        return [
            'variant_id' => ProductVariant::factory(),
            'url' => 'images/' . $this->faker->uuid() . '.jpg',
            'is_featured' => false,
        ];
    }
}
