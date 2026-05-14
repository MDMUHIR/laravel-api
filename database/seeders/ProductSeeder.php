<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\VariantImage;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'smart-speakers'],
            ['name' => 'Smart Speakers', 'status' => true]
        );

        $product = Product::updateOrCreate(
            ['slug' => 'hoco-hc2-wireless-bluetooth-speaker'],
            [
                'name' => 'HOCO HC2 Wireless Bluetooth Speaker',
                'description' => 'Experience powerful sound and premium design with the HOCO HC2 Wireless Bluetooth Speaker. Featuring advanced audio technology, this portable speaker delivers rich, immersive bass and crystal-clear highs. Perfect for outdoor adventures, parties, or everyday listening.',
                'short_description' => 'Portable Bluetooth speaker with rich bass',
                'price' => 2099,
                'offer_price' => 1441,
                'stock' => 23,
                'has_variants' => true,
                'status' => true,
                'category_id' => $category->id,
            ]
        );

        ProductImage::where('product_id', $product->id)->delete();
        foreach (['images/1778527736-6a022df8d8ec8.jpg', 'images/1778599509-6a03465556aaa.jpg'] as $image) {
            ProductImage::create(['product_id' => $product->id, 'image_path' => $image]);
        }

        $product->variants()->delete();

        $greenVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'HOCO-HC2-GREEN',
            'color' => 'Green',
            'color_code' => '#008000',
            'price' => 1441,
            'offer_price' => 1441,
            'stock' => 0,
        ]);
        VariantImage::create(['variant_id' => $greenVariant->id, 'image_path' => 'images/hoco-hc2-green-1.jpg']);
        VariantImage::create(['variant_id' => $greenVariant->id, 'image_path' => 'images/hoco-hc2-green-2.jpg']);

        $blackVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'HOCO-HC2-BLACK',
            'color' => 'Black',
            'color_code' => '#000000',
            'price' => 1499,
            'offer_price' => 1399,
            'stock' => 15,
        ]);
        VariantImage::create(['variant_id' => $blackVariant->id, 'image_path' => 'images/hoco-hc2-black-1.jpg']);

        $camoVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'HOCO-HC2-CAMO',
            'color' => 'Camouflage',
            'color_code' => '#4B5320',
            'price' => 1550,
            'offer_price' => 1441,
            'stock' => 8,
        ]);

        $product->update(['default_variant_id' => $greenVariant->id]);
    }
}
