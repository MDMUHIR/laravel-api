<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\VariantImage;
use App\Models\VariantAttribute;
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
                'status' => 'active',
                'category_id' => $category->id,
            ]
        );

        ProductImage::where('product_id', $product->id)->delete();
        foreach ([
            ['url' => 'images/1778527736-6a022df8d8ec8.jpg', 'is_featured' => true],
            ['url' => 'images/1778599509-6a03465556aaa.jpg', 'is_featured' => false],
        ] as $image) {
            ProductImage::create(array_merge(['product_id' => $product->id], $image));
        }

        $product->variants()->delete();

        $greenVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'HOCO-HC2-GREEN',
            'price' => 1441,
            'offer_price' => 1441,
            'stock' => 0,
        ]);
        VariantAttribute::create(['variant_id' => $greenVariant->id, 'attribute' => 'Color', 'value' => 'Green']);
        VariantImage::create(['variant_id' => $greenVariant->id, 'url' => 'images/hoco-hc2-green-1.jpg', 'is_featured' => true]);
        VariantImage::create(['variant_id' => $greenVariant->id, 'url' => 'images/hoco-hc2-green-2.jpg', 'is_featured' => false]);

        $blackVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'HOCO-HC2-BLACK',
            'price' => 1499,
            'offer_price' => 1399,
            'stock' => 15,
        ]);
        VariantAttribute::create(['variant_id' => $blackVariant->id, 'attribute' => 'Color', 'value' => 'Black']);
        VariantImage::create(['variant_id' => $blackVariant->id, 'url' => 'images/hoco-hc2-black-1.jpg', 'is_featured' => true]);

        $camoVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'HOCO-HC2-CAMO',
            'price' => 1550,
            'offer_price' => 1441,
            'stock' => 8,
        ]);
        VariantAttribute::create(['variant_id' => $camoVariant->id, 'attribute' => 'Color', 'value' => 'Camouflage']);
    }
}
