<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function getProducts(Request $request)
    {
        $products = Product::with('category', 'images')->get();

        return $this->success('Products retrieved successfully', $products);
    }

    public function addProduct(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'description' => 'required',
            'price' => 'required',
            'stock' => 'required',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'required',
        ]);

        $product = new Product;
        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->stock = $request->stock;
        // $product->status = $request->status;
        $product->category_id = $request->category_id;
        $product->save();

        // handle multiple image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $image_name = time().'-'.uniqid().'.'.$image->extension();
                $image->move(public_path('images'), $image_name);

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'images/'.$image_name,
                ]);
            }
        }

        $product->load('category', 'images');

        return $this->success('Product added successfully', $product);
    }

    public function deleteProduct(Request $request, $id)
    {
        $product = Product::find($id);
        if (! $product) {
            return $this->error('Product not found', 404);
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

    public function getSingleProduct(Request $request, $id)
    {
        $product = Product::with('category', 'images')->find($id);
        if (! $product) {
            return $this->error('Product not found', 404);
        }

        return $this->success('Product retrieved successfully', $product);
    }

    public function updateProduct(Request $request)
    {
        $this->validate($request, [
            'product_id' => 'required',
            'name' => 'required',
            'description' => 'required',
            'price' => 'required',
            'stock' => 'required',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'required',
        ]);

        $product = Product::find($request->product_id);
        if (! $product) {
            return $this->error('Product not found', 404);
        }

        // handle multiple image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $image_name = time().'-'.uniqid().'.'.$image->extension();
                $image->move(public_path('images'), $image_name);

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'images/'.$image_name,
                ]);
            }
        }

        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->stock = $request->stock;
        // $product->status = $request->status;
        $product->category_id = $request->category_id;
        $product->save();

        $product->load('category', 'images');

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
}
