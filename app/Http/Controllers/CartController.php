<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function getCart(Request $request)
    {
        $cart = Cart::with([
            'product.images',
            'variant.images',
        ])->where('user_id', $request->user()->id)->get();

        return $this->success('Get cart', $cart);
    }

    public function addToCart(Request $request)
    {
        $items = $request->items;

        if (! $items || ! is_array($items)) {
            $this->validate($request, [
                'product_id' => 'required|integer',
                'quantity' => 'required|integer|min:1',
            ]);

            $items = [[
                'product_id' => $request->product_id,
                'variant_id' => $request->variant_id ?? null,
                'quantity' => $request->quantity,
            ]];
        }

        $addedItems = [];
        $errors = [];

        foreach ($items as $index => $item) {
            $productId = $item['product_id'] ?? null;
            $variantId = $item['variant_id'] ?? null;
            $quantity = $item['quantity'] ?? 1;

            if (! $productId) {
                $errors[] = 'Item '.($index + 1).': Product ID is required';

                continue;
            }

            if (! is_numeric($quantity) || $quantity < 1) {
                $errors[] = 'Item '.($index + 1).': Quantity must be at least 1';

                continue;
            }

            $product = Product::find($productId);
            if (! $product) {
                $errors[] = 'Item '.($index + 1).': Product not found';

                continue;
            }

            $variant = null;
            $price = $item['price'] ?? null;

            if ($variantId) {
                $variant = ProductVariant::where('id', $variantId)
                    ->where('product_id', $productId)
                    ->first();

                if (! $variant) {
                    $errors[] = 'Item '.($index + 1).': Variant not found';

                    continue;
                }

                $price = $price ?? ($variant->offer_price ?? $variant->price);

                if ($variant->stock !== null && $quantity > $variant->stock) {
                    $errors[] = 'Item '.($index + 1).': Requested quantity exceeds available stock';

                    continue;
                }
            } elseif ($product->has_variants) {
                $errors[] = 'Item '.($index + 1).': Please select a variant';

                continue;
            } else {
                $price = $price ?? ($product->offer_price ?? $product->price);

                if ($product->stock !== null && $quantity > $product->stock) {
                    $errors[] = 'Item '.($index + 1).': Requested quantity exceeds available stock';

                    continue;
                }
            }

            $query = Cart::where('user_id', $request->user()->id)
                ->where('product_id', $productId);

            if ($variantId) {
                $query->where('variant_id', $variantId);
            } else {
                $query->whereNull('variant_id');
            }

            $existCart = $query->first();

            if ($existCart) {
                $newQuantity = $existCart->quantity + $quantity;

                if ($variant && $variant->stock !== null && $newQuantity > $variant->stock) {
                    $errors[] = 'Item '.($index + 1).': Requested quantity exceeds available stock';

                    continue;
                } elseif (! $variant && $product->stock !== null && $newQuantity > $product->stock) {
                    $errors[] = 'Item '.($index + 1).': Requested quantity exceeds available stock';

                    continue;
                }

                $existCart->quantity = $newQuantity;
                $existCart->total = $existCart->quantity * $existCart->price;
                $existCart->save();
                $addedItems[] = $existCart;
            } else {
                $cart = new Cart;
                $cart->user_id = $request->user()->id;
                $cart->product_id = $productId;
                $cart->variant_id = $variantId;
                $cart->quantity = $quantity;
                $cart->price = $price;
                $cart->total = $quantity * $price;
                $cart->save();
                $addedItems[] = $cart;
            }
        }

        if (empty($addedItems) && ! empty($errors)) {
            return $this->error(implode('. ', $errors), 400);
        }

        if (! empty($errors)) {
            return $this->success('Some items added to cart with warnings', [
                'added' => $addedItems,
                'warnings' => $errors,
            ]);
        }

        return $this->success('Added to cart', $addedItems);
    }

    public function updateCart(Request $request)
    {
        $this->validate($request, [
            'cart_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::where('user_id', $request->user()->id)
            ->where('id', $request->cart_id)
            ->first();

        if (! $cart) {
            return $this->error('Cart item not found', 404);
        }

        if ($cart->variant) {
            if ($cart->variant->stock !== null && $request->quantity > $cart->variant->stock) {
                return $this->error('Requested quantity exceeds available stock', 400);
            }
        } elseif ($cart->product->stock !== null && $request->quantity > $cart->product->stock) {
            return $this->error('Requested quantity exceeds available stock', 400);
        }

        $cart->quantity = $request->quantity;
        $cart->total = $request->quantity * $cart->price;
        $cart->save();

        return $this->success('Update cart', $cart);
    }

    public function deleteCart(Request $request, $id)
    {
        $cart = Cart::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (! $cart) {
            return $this->error('Cart item not found', 404);
        }

        $cart->delete();

        return $this->success('Delete cart', null);
    }

    public function toggleSelection(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)
            ->where('id', $request->cart_id)
            ->first();

        if (! $cart) {
            return $this->error('Cart item not found', 404);
        }

        $cart->is_selected = $request->is_selected;
        $cart->save();

        return $this->success('Cart selection updated', $cart);
    }

    public function selectAll(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)
            ->where('is_selected', false)
            ->get();

        if ($cart->isEmpty()) {
            return $this->error('No unselected items in cart', 400);
        }

        $count = $cart->count();
        Cart::where('user_id', $request->user()->id)
            ->where('is_selected', false)
            ->update(['is_selected' => true]);

        return $this->success("Selected $count items for checkout", null);
    }

    public function deselectAll(Request $request)
    {
        $count = Cart::where('user_id', $request->user()->id)
            ->where('is_selected', true)
            ->count();

        if ($count === 0) {
            return $this->error('No selected items in cart', 400);
        }

        Cart::where('user_id', $request->user()->id)
            ->where('is_selected', true)
            ->update(['is_selected' => false]);

        return $this->success("Deselected $count items", null);
    }
}
