<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function addOrder(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)->where('is_selected', true)->get();

        if ($cart->isEmpty()) {
            return $this->error('No items selected for order', 400);
        }

        // Wrap order creation and stock updates in a transaction
        try {
            $order = DB::transaction(function () use ($request, $cart) {
                $total = 0;

                $order = new Order;
                $order->user_id = $request->user()->id;
                $order->status = 'pending';
                $order->payment_method = $request->payment_method;
                $order->payment_status = 'pending';
                $order->name = $request->name;
                $order->phone = $request->phone;
                $order->phone_alt = $request->phone_alt;
                $order->email = $request->email;
                $order->line1 = $request->line1;
                $order->line2 = $request->line2;
                $order->city = $request->city;
                $order->country = $request->country;
                $order->coupon = $request->coupon;
                $order->notes = $request->notes;
                $order->save();

                foreach ($cart as $item) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                    if (! $product) {
                        throw new \Exception('Product not found: '.$item->product_id);
                    }

                    if (! is_null($product->stock) && $item->quantity > $product->stock) {
                        throw new \Exception('Insufficient stock for product: '.$product->name);
                    }

                    $OrderProduct = new OrderProduct;
                    $OrderProduct->order_id = $order->id;
                    $OrderProduct->product_id = $item->product_id;
                    $OrderProduct->quantity = $item->quantity;
                    $OrderProduct->price = $item->price;
                    $OrderProduct->save();

                    // decrement stock if set (use atomic DB decrement)
                    if (! is_null($product->stock)) {
                        $decrement = min($item->quantity, $product->stock);
                        if ($decrement > 0) {
                            $product->decrement('stock', $decrement);
                        }
                    }

                    $total += $item->quantity * $item->price;
                }

                // remove cart items that were selected
                Cart::where('user_id', $request->user()->id)->where('is_selected', true)->delete();

                $order->total = $this->calculateTotals($request->coupon, $total);
                $order->save();

                return $order;
            });
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->success('Add order', $order);
    }

    public function calculateTotals($coupon, $total)
    {
        if ($coupon) {
            $coupon = Coupon::where('code', $coupon)->first();
            if ($coupon) {
                if ($coupon->type == 'fixed') {
                    $total = $total - $coupon->discount;
                } else {
                    $total = $total - ($total * $coupon->discount / 100);
                }
            }
        }

        return $total;
    }

    public function getOrder(Request $request)
    {
        $orders = Order::with('products.images')->where('user_id', $request->user()->id)->get();

        // foreach($orders as $order){
        //     $order->products;
        // }
        return $this->success('Get orders', $orders);
    }

    public function getAdminOrders(Request $request)
    {
        $orders = Order::with('products.images')->with('user')->get();

        return $this->success('Get orders', $orders);
    }

    public function getAdminOrder(Request $request, $id)
    {
        $order = Order::with('products.images')->with('user')->where('id', $id)->first();

        return $this->success('Get single order', $order);
    }

    public function updateAdminOrder(Request $request)
    {
        $order = Order::find($request->id);
        $order->status = $request->status;
        $order->notes = $request->notes;
        $order->save();

        $products = $request->products;

        foreach ($products as $product) {
            $OrderProduct = OrderProduct::where('order_id', $order->id)->where('product_id', $product['id'])->first();
            $OrderProduct->quantity = $product['pivot']['quantity'];
            $OrderProduct->save();
        }

        return $this->success('Update order', $order);
    }

    public function directOrder(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric',
            'payment_method' => 'required|string',
            'name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'line1' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
        ]);

        $product = Product::where('id', $request->product_id)->first();

        if (! $product) {
            return $this->error('Product not found', 404);
        }

        if (! is_null($product->stock) && $request->quantity > $product->stock) {
            return $this->error('Insufficient stock for product: '.$product->name, 400);
        }

        try {
            $order = DB::transaction(function () use ($request, $product) {
                $product = Product::where('id', $request->product_id)->lockForUpdate()->first();

                $order = new Order;
                $order->user_id = $request->user()->id;
                $order->status = 'pending';
                $order->payment_method = $request->payment_method;
                $order->payment_status = 'pending';
                $order->name = $request->name;
                $order->phone = $request->phone;
                $order->phone_alt = $request->phone_alt;
                $order->email = $request->email;
                $order->line1 = $request->line1;
                $order->line2 = $request->line2;
                $order->city = $request->city;
                $order->country = $request->country;
                $order->coupon = $request->coupon;
                $order->notes = $request->notes;
                $order->save();

                $OrderProduct = new OrderProduct;
                $OrderProduct->order_id = $order->id;
                $OrderProduct->product_id = $request->product_id;
                $OrderProduct->quantity = $request->quantity;
                $OrderProduct->price = $request->price;
                $OrderProduct->save();

                if (! is_null($product->stock)) {
                    $decrement = min($request->quantity, $product->stock);
                    if ($decrement > 0) {
                        $product->decrement('stock', $decrement);
                    }
                }

                $total = $request->quantity * $request->price;
                $order->total = $this->calculateTotals($request->coupon, $total);
                $order->save();

                return $order;
            });
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->success('Order placed successfully', $order);
    }
}
