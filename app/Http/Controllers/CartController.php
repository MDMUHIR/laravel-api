<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function getCart(Request $request)
    {
        $cart = Cart::with('product.images')->where('user_id', $request->user()->id)->get();

        return $this->success('Get cart', $cart);
    }

    public function addToCart(Request $request)
    {

        $this->validate($request, [
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric',
        ]);

        $product = Product::find($request->product_id);
        if (! $product) {
            return $this->error('Product not found', 404);
        }

        // If product stock is set, ensure requested quantity does not exceed available stock
        if (! is_null($product->stock) && $request->quantity > $product->stock) {
            return $this->error('Requested quantity exceeds available stock', 400);
        }

        $existCart = Cart::where('user_id', $request->user()->id)->where('product_id', $request->product_id)->first();

        if ($existCart) {
            $newQuantity = $existCart->quantity + $request->quantity;

            // If product stock is set, ensure new quantity does not exceed available stock
            if (! is_null($product->stock) && $newQuantity > $product->stock) {
                return $this->error('Requested quantity exceeds available stock', 400);
            }

            $existCart->quantity = $newQuantity;
            $existCart->total = $existCart->quantity * $existCart->price;
            $existCart->save();

            return $this->success('Add to cart', $existCart);
        }

        $cart = new Cart;
        $cart->user_id = $request->user()->id;
        $cart->product_id = $request->product_id;
        $cart->quantity = $request->quantity;
        $cart->price = $request->price;
        $cart->total = $request->quantity * $request->price;
        $cart->save();

        return $this->success('Add to cart', $cart);
    }

    public function updateCart(Request $request)
    {

        $this->validate($request, [
            'cart_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->where('id', $request->cart_id)->first();

        if (! $cart) {
            return $this->error('Cart item not found', 404);
        }

        $product = Product::find($cart->product_id);
        if (! $product) {
            return $this->error('Product not found', 404);
        }

        // If product stock is set, ensure requested quantity does not exceed available stock
        if (! is_null($product->stock) && $request->quantity > $product->stock) {
            return $this->error('Requested quantity exceeds available stock', 400);
        }

        $cart->quantity = $request->quantity;
        $cart->total = $request->quantity * $cart->price;
        $cart->save();

        return $this->success('Update cart', $cart);
    }

    public function deleteCart(Request $request, $id)
    {
        $cart = Cart::where('user_id', $request->user()->id)->where('id', $id)->first();
        $cart->delete();

        return $this->success('Delete cart', $cart);
    }

    public function toggleSelection(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)->where('id', $request->cart_id)->first();

        if (! $cart) {
            return $this->error('Cart item not found', 404);
        }

        $cart->is_selected = $request->is_selected;
        $cart->save();

        return $this->success('Cart selection updated', $cart);
    }
}
