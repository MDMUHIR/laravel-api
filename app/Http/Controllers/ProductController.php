<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\VariantImage;
use App\Models\VariantAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function getProducts(Request $request)
    {
        $products = Product::with('category', 'images', 'variants.images', 'variants.attributes')->get();

        return $this->success('Products retrieved successfully', $products);
    }

    public function addProduct(Request $request)
    {
        if ($request->has('slug') && $request->slug === '') {
            $request->merge(['slug' => null]);
        }

        $rules = [
            'name' => 'required',
            'slug' => 'nullable|string|unique:products,slug',
            'description' => 'nullable',
            'short_description' => 'nullable',
            'price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|string|in:active,draft,discontinued',
            'images' => 'nullable',
            'variants' => 'nullable|array',
            'variants.*.sku' => 'nullable|string',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.offer_price' => 'nullable|numeric|min:0',
            'variants.*.stock' => 'nullable|integer|min:0',
            'variants.*.attributes' => 'nullable|array',
            'variants.*.attributes.*.attribute' => 'required_with:variants.*.attributes|string',
            'variants.*.attributes.*.value' => 'required_with:variants.*.attributes|string',
            'variants.*.images' => 'nullable',
        ];

        $this->validate($request, $rules);

        $product = Product::create([
            'name' => $request->name,
            'slug' => $request->slug ?? Str::slug($request->name),
            'description' => $request->description ?? '',
            'short_description' => $request->short_description ?? '',
            'price' => $request->price ?? 0,
            'offer_price' => $request->offer_price,
            'stock' => $request->stock ?? 0,
            'category_id' => $request->category_id,
            'status' => $request->status ?? 'active',
        ]);

        if ($request->has('images')) {
            $this->saveProductImages($product->id, $request->input('images'), $request, 'images');
        }

        if ($request->has('variants')) {
            foreach ($request->variants as $index => $variantData) {
                $sku = $variantData['sku'] ?? strtoupper(Str::slug($request->name)).'-'.Str::upper(Str::random(3)).'-'.($index + 1);

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $sku,
                    'price' => $variantData['price'] ?? 0,
                    'offer_price' => $variantData['offer_price'] ?? null,
                    'stock' => $variantData['stock'] ?? 0,
                ]);

                if (!empty($variantData['attributes'])) {
                    foreach ($variantData['attributes'] as $attr) {
                        VariantAttribute::create([
                            'variant_id' => $variant->id,
                            'attribute' => $attr['attribute'],
                            'value' => $attr['value'],
                        ]);
                    }
                }

                $imagesKey = "variants.{$index}.images";
                if ($request->has($imagesKey)) {
                    $images = is_array($variantData['images'] ?? null) ? $variantData['images'] : [];
                    $this->saveVariantImages($variant->id, $images, $request, $imagesKey);
                }
            }
        }

        $product->load('category', 'images', 'variants.images', 'variants.attributes');

        return $this->success('Product added successfully', $product);
    }

    public function deleteProduct(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return $this->error('Product not found', 404);
        }

        foreach ($product->variants as $variant) {
            foreach ($variant->images as $img) {
                @unlink(public_path($img->url));
                $img->delete();
            }
            $variant->attributes()->delete();
            $variant->delete();
        }

        foreach ($product->images as $image) {
            @unlink(public_path($image->url));
            $image->delete();
        }

        $product->delete();

        return $this->success('Product deleted successfully');
    }

    public function getSingleProduct(Request $request, $identifier)
    {
        $query = Product::with('category', 'images', 'variants.images', 'variants.attributes');

        if (is_numeric($identifier)) {
            $product = $query->find($identifier);
        } else {
            $product = $query->where('slug', $identifier)->first();
        }

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        return $this->success('Product retrieved successfully', $product);
    }

    public function updateProduct(Request $request)
    {
        if ($request->has('slug') && $request->slug === '') {
            $request->merge(['slug' => null]);
        }

        $rules = [
            'product_id' => 'required|exists:products,id',
            'name' => 'required',
            'slug' => 'nullable|string|unique:products,slug,'.$request->product_id,
            'description' => 'nullable',
            'short_description' => 'nullable',
            'price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|string|in:active,draft,discontinued',
            'images' => 'nullable',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.sku' => 'nullable|string',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.offer_price' => 'nullable|numeric|min:0',
            'variants.*.stock' => 'nullable|integer|min:0',
            'variants.*.attributes' => 'nullable|array',
            'variants.*.attributes.*.attribute' => 'required_with:variants.*.attributes|string',
            'variants.*.attributes.*.value' => 'required_with:variants.*.attributes|string',
            'variants.*.images' => 'nullable',
            'variants.*.images_to_keep' => 'nullable|array',
            'variants.*.images_to_keep.*' => 'integer',
        ];

        $this->validate($request, $rules);

        $product = Product::find($request->product_id);
        if (!$product) {
            return $this->error('Product not found', 404);
        }

        $updateData = [];
        if ($request->has('name')) $updateData['name'] = $request->name;
        if ($request->has('slug')) $updateData['slug'] = $request->slug;
        if ($request->has('description')) $updateData['description'] = $request->description;
        if ($request->has('short_description')) $updateData['short_description'] = $request->short_description;
        if ($request->has('price')) $updateData['price'] = $request->price;
        if ($request->has('offer_price')) $updateData['offer_price'] = $request->offer_price;
        if ($request->has('stock')) $updateData['stock'] = $request->stock;
        if ($request->has('category_id')) $updateData['category_id'] = $request->category_id;
        if ($request->has('status')) $updateData['status'] = $request->status;
        if (!empty($updateData)) {
            $product->update($updateData);
        }

        if ($request->has('images')) {
            $this->saveProductImages($product->id, $request->input('images'), $request, 'images');
        }

        if ($request->has('variants')) {
            $existingVariantIds = $product->variants()->pluck('id')->toArray();
            $newVariantIds = [];

            foreach ($request->variants as $index => $variantData) {
                $imagesToKeep = $variantData['images_to_keep'] ?? [];
                $variant = null;

                if (isset($variantData['id'])) {
                    $variant = ProductVariant::find($variantData['id']);
                    if ($variant && $variant->product_id == $product->id) {
                        $updateData = [];
                        if (isset($variantData['sku'])) $updateData['sku'] = $variantData['sku'];
                        if (isset($variantData['price'])) $updateData['price'] = $variantData['price'];
                        if (array_key_exists('offer_price', $variantData)) $updateData['offer_price'] = $variantData['offer_price'];
                        if (isset($variantData['stock'])) $updateData['stock'] = $variantData['stock'];
                        $variant->update($updateData);
                        $newVariantIds[] = $variant->id;
                    } else {
                        $variant = null;
                    }
                }

                if (!$variant) {
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $variantData['sku'] ?? 'SKU-'.Str::upper(Str::random(6)),
                        'price' => $variantData['price'] ?? 0,
                        'offer_price' => $variantData['offer_price'] ?? null,
                        'stock' => $variantData['stock'] ?? 0,
                    ]);
                    $newVariantIds[] = $variant->id;
                }

                if (isset($variantData['attributes'])) {
                    $variant->attributes()->delete();
                    foreach ($variantData['attributes'] as $attr) {
                        VariantAttribute::create([
                            'variant_id' => $variant->id,
                            'attribute' => $attr['attribute'],
                            'value' => $attr['value'],
                        ]);
                    }
                }

                if (array_key_exists('images_to_keep', $variantData) || array_key_exists('images', $variantData)) {
                    foreach ($variant->images as $img) {
                        if (!in_array($img->id, $imagesToKeep)) {
                            @unlink(public_path($img->url));
                            $img->delete();
                        }
                    }
                }

                $imagesKey = "variants.{$index}.images";
                if ($request->has($imagesKey)) {
                    $images = is_array($variantData['images'] ?? null) ? $variantData['images'] : [];
                    $this->saveVariantImages($variant->id, $images, $request, $imagesKey);
                }
            }

            $variantsToDelete = array_diff($existingVariantIds, $newVariantIds);
            if (!empty($variantsToDelete)) {
                $variantsToDeleteModels = ProductVariant::whereIn('id', $variantsToDelete)->get();
                foreach ($variantsToDeleteModels as $v) {
                    foreach ($v->images as $img) {
                        @unlink(public_path($img->url));
                        $img->delete();
                    }
                    $v->attributes()->delete();
                    $v->delete();
                }
            }
        }

        $product->load('category', 'images', 'variants.images', 'variants.attributes');

        return $this->success('Product updated successfully', $product);
    }

    public function deleteProductImage(Request $request, $productId, $imageId)
    {
        $image = ProductImage::where('id', $imageId)->where('product_id', $productId)->first();
        if (!$image) {
            return $this->error('Image not found', 404);
        }

        @unlink(public_path($image->url));
        $image->delete();

        return $this->success('Image deleted successfully');
    }

    public function searchProducts(Request $request)
    {
        $query = Product::with('category', 'images', 'variants.images', 'variants.attributes');

        $searchTerm = $request->get('q') ?? $request->get('search');

        if ($searchTerm && !empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%'.$searchTerm.'%')
                    ->orWhere('description', 'LIKE', '%'.$searchTerm.'%')
                    ->orWhereHas('variants', function ($v) use ($searchTerm) {
                        $v->where('sku', 'LIKE', '%'.$searchTerm.'%')
                          ->orWhereHas('attributes', function ($a) use ($searchTerm) {
                              $a->where('value', 'LIKE', '%'.$searchTerm.'%');
                          });
                    });
            });
        }

        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('min_price') && !empty($request->min_price)) {
            $query->where(function ($q) use ($request) {
                $q->where('price', '>=', $request->min_price)
                  ->orWhereHas('variants', function ($v) use ($request) {
                      $v->where('price', '>=', $request->min_price);
                  });
            });
        }

        if ($request->has('max_price') && !empty($request->max_price)) {
            $query->where(function ($q) use ($request) {
                $q->where('price', '<=', $request->max_price)
                  ->orWhereHas('variants', function ($v) use ($request) {
                      $v->where('price', '<=', $request->max_price);
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

    private function saveProductImages($productId, $images, Request $request, $baseKey)
    {
        if ($images === null) {
            if ($request->hasFile($baseKey)) {
                $files = $request->file($baseKey);
                $images = [];
                foreach ($files as $i => $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        $images[] = ['url' => $file, 'is_featured' => false];
                    }
                }
            }
        }

        if (empty($images) || !is_array($images)) {
            return;
        }

        foreach ($images as $index => $imageData) {
            if (is_string($imageData)) {
                continue;
            }

            $isFeatured = filter_var($imageData['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $url = null;

            $fileKey = $baseKey.'.'.$index.'.url';
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $imageName = time().'-'.uniqid().'.'.$file->extension();
                $file->move(public_path('images'), $imageName);
                $url = 'images/'.$imageName;
            } elseif (!empty($imageData['url']) && is_string($imageData['url'])) {
                $url = $imageData['url'];
            } elseif (isset($imageData['url']) && $imageData['url'] instanceof \Illuminate\Http\UploadedFile) {
                $imageName = time().'-'.uniqid().'.'.$imageData['url']->extension();
                $imageData['url']->move(public_path('images'), $imageName);
                $url = 'images/'.$imageName;
            } elseif (isset($imageData['url']) && is_array($imageData['url'])) {
                continue;
            }

            if ($url) {
                ProductImage::create([
                    'product_id' => $productId,
                    'url' => $url,
                    'is_featured' => $isFeatured,
                ]);
            }
        }
    }

    private function saveVariantImages($variantId, $images, Request $request, $baseKey)
    {
        if ($images === null) {
            if ($request->hasFile($baseKey)) {
                $files = $request->file($baseKey);
                $images = [];
                foreach ($files as $i => $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        $images[] = ['url' => $file, 'is_featured' => false];
                    }
                }
            }
        }

        if (empty($images) || !is_array($images)) {
            return;
        }

        foreach ($images as $index => $imageData) {
            if (is_string($imageData)) {
                continue;
            }

            $isFeatured = filter_var($imageData['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $url = null;

            $fileKey = $baseKey.'.'.$index.'.url';
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $imageName = time().'-'.uniqid().'.'.$file->extension();
                $file->move(public_path('images'), $imageName);
                $url = 'images/'.$imageName;
            } elseif (!empty($imageData['url']) && is_string($imageData['url'])) {
                $url = $imageData['url'];
            } elseif (isset($imageData['url']) && $imageData['url'] instanceof \Illuminate\Http\UploadedFile) {
                $imageName = time().'-'.uniqid().'.'.$imageData['url']->extension();
                $imageData['url']->move(public_path('images'), $imageName);
                $url = 'images/'.$imageName;
            } elseif (isset($imageData['url']) && is_array($imageData['url'])) {
                continue;
            }

            if ($url) {
                VariantImage::create([
                    'variant_id' => $variantId,
                    'url' => $url,
                    'is_featured' => $isFeatured,
                ]);
            }
        }
    }

    public function addVariant(Request $request, $id)
    {
        $this->validate($request, [
            'sku' => 'nullable|unique:product_variants,sku',
            'price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'attributes' => 'nullable|array',
            'attributes.*.attribute' => 'required_with:attributes|string',
            'attributes.*.value' => 'required_with:attributes|string',
            'images' => 'nullable',
        ]);

        $product = Product::find($id);
        if (!$product) {
            return $this->error('Product not found', 404);
        }

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $request->sku ?? 'SKU-'.Str::upper(Str::random(6)),
            'price' => $request->price ?? 0,
            'offer_price' => $request->offer_price,
            'stock' => $request->stock ?? 0,
        ]);

        $attrs = $request->input('attributes');
        if (!empty($attrs) && is_array($attrs)) {
            foreach ($attrs as $attr) {
                VariantAttribute::create([
                    'variant_id' => $variant->id,
                    'attribute' => $attr['attribute'],
                    'value' => $attr['value'],
                ]);
            }
        }

        if ($request->has('images')) {
            $images = $request->input('images');
            $this->saveVariantImages($variant->id, $images, $request, 'images');
        }

        $variant->load('images', 'attributes');

        return $this->success('Variant added successfully', $variant);
    }

    public function getAdminProduct(Request $request, $slug)
    {
        $product = Product::with('category', 'images', 'variants.images', 'variants.attributes')
            ->where('slug', $slug)->first();

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        return $this->success('Product retrieved successfully', $product);
    }

    public function getAdminVariant(Request $request, $id)
    {
        $variant = ProductVariant::with('product', 'images', 'attributes')->find($id);

        if (!$variant) {
            return $this->error('Variant not found', 404);
        }

        return $this->success('Variant retrieved successfully', $variant);
    }

    public function deleteVariant(Request $request, $id)
    {
        $variant = ProductVariant::find($id);
        if (!$variant) {
            return $this->error('Variant not found', 404);
        }

        foreach ($variant->images as $img) {
            @unlink(public_path($img->url));
            $img->delete();
        }

        $variant->attributes()->delete();
        $variant->delete();

        return $this->success('Variant deleted successfully');
    }

    public function updateVariant(Request $request, $id)
    {
        $this->validate($request, [
            'sku' => 'nullable|unique:product_variants,sku,'.$id,
            'price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'attributes' => 'nullable|array',
            'attributes.*.attribute' => 'required_with:attributes|string',
            'attributes.*.value' => 'required_with:attributes|string',
            'images' => 'nullable',
            'images_to_keep' => 'nullable|array',
            'images_to_keep.*' => 'integer',
        ]);

        $variant = ProductVariant::find($id);
        if (!$variant) {
            return $this->error('Variant not found', 404);
        }

        $updateData = [];
        if ($request->has('sku')) $updateData['sku'] = $request->sku;
        if ($request->has('price')) $updateData['price'] = $request->price;
        if ($request->has('offer_price')) $updateData['offer_price'] = $request->offer_price;
        if ($request->has('stock')) $updateData['stock'] = $request->stock;
        if (!empty($updateData)) {
            $variant->update($updateData);
        }

        $attrs = $request->input('attributes');
        if (!empty($attrs) && is_array($attrs)) {
            $variant->attributes()->delete();
            foreach ($attrs as $attr) {
                VariantAttribute::create([
                    'variant_id' => $variant->id,
                    'attribute' => $attr['attribute'],
                    'value' => $attr['value'],
                ]);
            }
        }

        if ($request->has('images_to_keep') || $request->has('images')) {
            $keepIds = $request->input('images_to_keep', []);

            foreach ($variant->images as $img) {
                if (!in_array($img->id, $keepIds)) {
                    @unlink(public_path($img->url));
                    $img->delete();
                }
            }

            if ($request->has('images')) {
                $images = $request->input('images');
                $this->saveVariantImages($variant->id, $images, $request, 'images');
            }
        }

        $variant->load('images', 'attributes');

        return $this->success('Variant updated successfully', $variant);
    }

    public function addVariantImage(Request $request, $id)
    {
        $this->validate($request, [
            'url' => 'nullable',
            'is_featured' => 'nullable|boolean',
        ]);

        $variant = ProductVariant::find($id);
        if (!$variant) {
            return $this->error('Variant not found', 404);
        }

        $isFeatured = filter_var($request->input('is_featured', false), FILTER_VALIDATE_BOOLEAN);
        $url = null;

        if ($request->hasFile('url')) {
            $file = $request->file('url');
            $imageName = time().'-'.uniqid().'.'.$file->extension();
            $file->move(public_path('images'), $imageName);
            $url = 'images/'.$imageName;
        } elseif ($request->filled('url')) {
            $url = $request->url;
        }

        if (!$url) {
            return $this->error('Image URL or file is required', 400);
        }

        $variantImage = VariantImage::create([
            'variant_id' => $variant->id,
            'url' => $url,
            'is_featured' => $isFeatured,
        ]);

        return $this->success('Image added successfully', $variantImage);
    }

    public function deleteVariantImage(Request $request, $variantId, $imageId)
    {
        $image = VariantImage::where('id', $imageId)->where('variant_id', $variantId)->first();
        if (!$image) {
            return $this->error('Image not found', 404);
        }

        @unlink(public_path($image->url));
        $image->delete();

        return $this->success('Image deleted successfully');
    }
}
