<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\VariantImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function getProducts(Request $request)
    {
        $products = Product::with('category', 'images', 'variants')->get();

        return $this->success('Products retrieved successfully', $products);
    }

    public function addProduct(Request $request)
    {
        $hasVariants = $request->boolean('has_variants');

        $rules = [
            'name' => 'required',
            'slug' => 'nullable|unique:products,slug',
            'description' => 'nullable',
            'short_description' => 'nullable',
            'price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'category_id' => 'required',
            'has_variants' => 'nullable|boolean',
        ];

        if ($request->hasFile('images')) {
            $rules['images'] = 'array';
            $rules['images.*'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        if (!$hasVariants) {
            $rules['price'] = 'required|numeric|min:0';
            $rules['stock'] = 'required|integer|min:0';
        }

        if ($request->has('variants')) {
            $rules['variants'] = 'array';
            $rules['variants.*.color'] = 'nullable|string';
            $rules['variants.*.color_code'] = 'nullable|string';
            $rules['variants.*.price'] = 'nullable|numeric|min:0';
            $rules['variants.*.offer_price'] = 'nullable|numeric|min:0';
            $rules['variants.*.stock'] = 'nullable|integer|min:0';
            if ($request->hasFile('variants')) {
                $rules['variants.*.images'] = 'array';
                $rules['variants.*.images.*'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
            }
        }

        $this->validate($request, $rules);

        $hasVariants = $request->boolean('has_variants');

        $product = Product::create([
            'name' => $request->name,
            'slug' => $request->slug ?? Str::slug($request->name),
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $hasVariants ? 0 : $request->price,
            'offer_price' => $hasVariants ? null : $request->offer_price,
            'stock' => $hasVariants ? 0 : $request->stock,
            'category_id' => $request->category_id,
            'has_variants' => $hasVariants,
            'status' => true,
        ]);

        if ($request->hasFile('images')) {
            $this->saveProductImages($product->id, $request->file('images'), $request->input('image_colors', []));
        }

        if ($hasVariants && $request->has('variants')) {
            foreach ($request->variants as $index => $variantData) {
                $variantImages = $variantData['images'] ?? [];
                unset($variantData['images']);

                $sku = $variantData['sku'] ?? strtoupper(Str::slug($request->name)).'-'.Str::upper(Str::random(3)).'-'.($index + 1);

                $variant = ProductVariant::create(array_merge($variantData, [
                    'product_id' => $product->id,
                    'sku' => $sku,
                ]));

                if (!empty($variantImages)) {
                    foreach ($variantImages as $img) {
                        $imageName = time().'-'.uniqid().'.'.$img->extension();
                        $img->move(public_path('images'), $imageName);
                        VariantImage::create([
                            'variant_id' => $variant->id,
                            'image_path' => 'images/'.$imageName,
                        ]);
                    }
                }
            }

            $firstVariant = ProductVariant::where('product_id', $product->id)->first();
            if ($firstVariant) {
                $product->update(['default_variant_id' => $firstVariant->id]);
            }
        }

        $product->load('category', 'images', 'variants.images');

        return $this->success('Product added successfully', $product);
    }

    public function deleteProduct(Request $request, $id)
    {
        $product = Product::find($id);
        if (! $product) {
            return $this->error('Product not found', 404);
        }

        // Delete variant images
        foreach ($product->variants as $variant) {
            foreach ($variant->images as $img) {
                @unlink(public_path($img->image_path));
                $img->delete();
            }
            $variant->delete();
        }

        // Delete all product images
        foreach ($product->images as $image) {
            @unlink(public_path($image->image_path));
            $image->delete();
        }

        // Delete old single image if it exists
        if ($product->image) {
            @unlink(public_path($product->image));
        }

        $product->delete();

        return $this->success('Product deleted successfully');
    }

    public function getSingleProduct(Request $request, $identifier)
    {
        $query = Product::with('category', 'images', 'variants.images');

        if (is_numeric($identifier)) {
            $product = $query->find($identifier);
        } else {
            $product = $query->where('slug', $identifier)->first();
        }

        if (! $product) {
            return $this->error('Product not found', 404);
        }

        return $this->success('Product retrieved successfully', $product);
    }

    public function updateProduct(Request $request)
    {
        $hasVariants = $request->boolean('has_variants');

        $rules = [
            'product_id' => 'required',
            'name' => 'required',
            'slug' => 'nullable|unique:products,slug,'.$request->product_id,
            'description' => 'nullable',
            'short_description' => 'nullable',
            'price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'category_id' => 'required',
            'has_variants' => 'nullable|boolean',
        ];

        if ($request->hasFile('images')) {
            $rules['images'] = 'array';
            $rules['images.*'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        if (!$hasVariants) {
            $rules['price'] = 'required|numeric|min:0';
            $rules['stock'] = 'required|integer|min:0';
        }

        if ($request->has('variants')) {
            $rules['variants'] = 'array';
            $rules['variants.*.color'] = 'nullable|string';
            $rules['variants.*.color_code'] = 'nullable|string';
            $rules['variants.*.price'] = 'nullable|numeric|min:0';
            $rules['variants.*.offer_price'] = 'nullable|numeric|min:0';
            $rules['variants.*.stock'] = 'nullable|integer|min:0';
            if ($request->hasFile('variants')) {
                $rules['variants.*.images'] = 'array';
                $rules['variants.*.images.*'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
            }
        }

        $this->validate($request, $rules);

        $product = Product::find($request->product_id);
        if (! $product) {
            return $this->error('Product not found', 404);
        }

        if ($request->hasFile('images')) {
            $this->saveProductImages($product->id, $request->file('images'), $request->input('image_colors', []));
        }

        $product->update([
            'name' => $request->name,
            'slug' => $request->slug ?? $product->slug,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $hasVariants ? 0 : $request->price,
            'offer_price' => $hasVariants ? null : $request->offer_price,
            'stock' => $hasVariants ? 0 : $request->stock,
            'category_id' => $request->category_id,
            'has_variants' => $hasVariants,
        ]);

        if ($hasVariants && $request->has('variants')) {
            $existingVariantIds = $product->variants()->pluck('id')->toArray();
            $newVariantIds = [];

            foreach ($request->variants as $variantData) {
                $variantImages = $variantData['images'] ?? [];
                unset($variantData['images']);

                if (isset($variantData['id'])) {
                    $variant = ProductVariant::find($variantData['id']);
                    if ($variant && $variant->product_id == $product->id) {
                        $variant->update(array_filter($variantData, fn($v) => $v !== null));
                        $newVariantIds[] = $variant->id;
                    }
                } else {
                    $variant = ProductVariant::create(array_merge($variantData, [
                        'product_id' => $product->id,
                        'sku' => $variantData['sku'] ?? 'SKU-'.Str::upper(Str::random(6)),
                    ]));
                    $newVariantIds[] = $variant->id;
                }

                $variantId = $variant->id ?? $variant->id;
                if (!empty($variantImages)) {
                    foreach ($variantImages as $img) {
                        $imageName = time().'-'.uniqid().'.'.$img->extension();
                        $img->move(public_path('images'), $imageName);
                        VariantImage::create([
                            'variant_id' => $variantId,
                            'image_path' => 'images/'.$imageName,
                        ]);
                    }
                }
            }

            $variantsToDelete = array_diff($existingVariantIds, $newVariantIds);
            if (!empty($variantsToDelete)) {
                $variantsToDeleteModels = ProductVariant::whereIn('id', $variantsToDelete)->get();
                foreach ($variantsToDeleteModels as $v) {
                    foreach ($v->images as $img) {
                        @unlink(public_path($img->image_path));
                        $img->delete();
                    }
                    $v->delete();
                }
            }

            if ($request->has('default_variant_id')) {
                $product->update(['default_variant_id' => $request->default_variant_id]);
            } elseif (!$product->default_variant_id || !in_array($product->default_variant_id, $newVariantIds)) {
                $firstVariant = ProductVariant::where('product_id', $product->id)->first();
                $product->update(['default_variant_id' => $firstVariant?->id]);
            }
        }

        $product->load('category', 'images', 'variants.images');

        return $this->success('Product updated successfully', $product);
    }

    public function deleteProductImage(Request $request, $productId, $imageId)
    {
        $product = Product::find($productId);
        if (! $product) {
            return $this->error('Product not found', 404);
        }

        $image = ProductImage::where('id', $imageId)->where('product_id', $productId)->first();
        if (! $image) {
            return $this->error('Image not found', 404);
        }

        @unlink(public_path($image->image_path));
        $image->delete();

        return $this->success('Image deleted successfully');
    }

    public function searchProducts(Request $request)
    {
        $query = Product::with('category', 'images', 'variants');

        $searchTerm = $request->get('q') ?? $request->get('search');
        
        if ($searchTerm && ! empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%'.$searchTerm.'%')
                    ->orWhere('description', 'LIKE', '%'.$searchTerm.'%')
                    ->orWhereHas('variants', function ($v) use ($searchTerm) {
                        $v->where('color', 'LIKE', '%'.$searchTerm.'%')
                          ->orWhere('sku', 'LIKE', '%'.$searchTerm.'%');
                    });
            });
        }

        if ($request->has('category_id') && ! empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('min_price') && ! empty($request->min_price)) {
            $query->where(function ($q) use ($request) {
                $q->where('price', '>=', $request->min_price)
                  ->orWhereHas('variants', function ($v) use ($request) {
                      $v->where('price', '>=', $request->min_price)
                        ->orWhere('offer_price', '>=', $request->min_price);
                  });
            });
        }

        if ($request->has('max_price') && ! empty($request->max_price)) {
            $query->where(function ($q) use ($request) {
                $q->where('price', '<=', $request->max_price)
                  ->orWhereHas('variants', function ($v) use ($request) {
                      $v->where('price', '<=', $request->max_price)
                        ->orWhere('offer_price', '<=', $request->max_price);
                  });
            });
        }

        if ($request->has('in_stock')) {
            $inStock = filter_var($request->in_stock, FILTER_VALIDATE_BOOLEAN);
            if ($inStock) {
                $query->where(function ($q) {
                    $q->where('stock', '>', 0)
                      ->orWhereHas('variants', function ($v) {
                          $v->where('stock', '>', 0);
                      });
                });
            }
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortFields = ['name', 'price', 'created_at', 'stock'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $perPage = $request->get('per_page', 12);
        $perPage = min(max($perPage, 1), 100);

        $products = $query->paginate($perPage);

        return $this->success('Products retrieved successfully', $products);
    }

    private function saveProductImages($productId, $images, $colors = [])
    {
        if (is_string($colors)) {
            $decoded = json_decode($colors, true);
            $colors = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : array_values(array_filter(array_map('trim', explode(',', $colors)), fn($v) => $v !== ''));
        } elseif (!is_array($colors)) {
            $colors = [];
        }

        foreach ($images as $index => $image) {
            $imageName = time().'-'.uniqid().'.'.$image->extension();
            $image->move(public_path('images'), $imageName);
            ProductImage::create([
                'product_id' => $productId,
                'image_path' => 'images/'.$imageName,
                'color' => $colors[$index] ?? null,
            ]);
        }
    }

    public function addVariant(Request $request, $id)
    {
        $this->validate($request, [
            'sku' => 'nullable|unique:product_variants,sku',
            'color' => 'nullable|string',
            'color_code' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $product = Product::find($id);
        if (! $product) {
            return $this->error('Product not found', 404);
        }

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $request->sku ?? 'SKU-'.Str::upper(Str::random(6)),
            'color' => $request->color,
            'color_code' => $request->color_code,
            'price' => $request->price ?? 0,
            'offer_price' => $request->offer_price,
            'stock' => $request->stock ?? 0,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $imageName = time().'-'.uniqid().'.'.$img->extension();
                $img->move(public_path('images'), $imageName);
                VariantImage::create([
                    'variant_id' => $variant->id,
                    'image_path' => 'images/'.$imageName,
                ]);
            }
        }

        if (!$product->default_variant_id) {
            $product->update(['default_variant_id' => $variant->id, 'has_variants' => true]);
        }

        $variant->load('images');

        return $this->success('Variant added successfully', $variant);
    }

    public function getAdminProduct(Request $request, $slug)
    {
        $product = Product::with('category', 'images', 'variants.images')->where('slug', $slug)->first();
        if (! $product) {
            return $this->error('Product not found', 404);
        }

        return $this->success('Product retrieved successfully', $product);
    }

    public function deleteVariant(Request $request, $id)
    {
        $variant = ProductVariant::find($id);
        if (! $variant) {
            return $this->error('Variant not found', 404);
        }

        foreach ($variant->images as $img) {
            @unlink(public_path($img->image_path));
            $img->delete();
        }

        $product = $variant->product;
        $variant->delete();

        if ($product->default_variant_id == $id) {
            $firstVariant = ProductVariant::where('product_id', $product->id)->first();
            $product->update(['default_variant_id' => $firstVariant?->id]);
        }

        return $this->success('Variant deleted successfully');
    }

    public function updateVariant(Request $request, $id)
    {
        $this->validate($request, [
            'sku' => 'nullable|unique:product_variants,sku,'.$id,
            'color' => 'nullable|string',
            'color_code' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $variant = ProductVariant::find($id);
        if (! $variant) {
            return $this->error('Variant not found', 404);
        }

        $variant->update(array_filter([
            'sku' => $request->sku,
            'color' => $request->color,
            'color_code' => $request->color_code,
            'price' => $request->price,
            'offer_price' => $request->offer_price,
            'stock' => $request->stock,
        ], fn($v) => $v !== null && $v !== ''));

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $imageName = time().'-'.uniqid().'.'.$img->extension();
                $img->move(public_path('images'), $imageName);
                VariantImage::create([
                    'variant_id' => $variant->id,
                    'image_path' => 'images/'.$imageName,
                ]);
            }
        }

        $variant->load('images');

        return $this->success('Variant updated successfully', $variant);
    }

    public function addVariantImage(Request $request, $id)
    {
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $variant = ProductVariant::find($id);
        if (! $variant) {
            return $this->error('Variant not found', 404);
        }

        $imageName = time().'-'.uniqid().'.'.$request->image->extension();
        $request->image->move(public_path('images'), $imageName);

        $variantImage = VariantImage::create([
            'variant_id' => $variant->id,
            'image_path' => 'images/'.$imageName,
        ]);

        return $this->success('Image added successfully', $variantImage);
    }

    public function deleteVariantImage(Request $request, $variantId, $imageId)
    {
        $image = VariantImage::where('id', $imageId)->where('variant_id', $variantId)->first();
        if (! $image) {
            return $this->error('Image not found', 404);
        }

        @unlink(public_path($image->image_path));
        $image->delete();

        return $this->success('Image deleted successfully');
    }
}
