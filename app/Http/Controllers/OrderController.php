<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function addOrder(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)
            ->where('is_selected', true)
            ->get();

        if ($cart->isEmpty()) {
            return $this->error('No items selected for order', 400);
        }

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
                    if (!$product) {
                        throw new \Exception('Product not found: ' . $item->product_id);
                    }

                    $variant = null;
                    $availableStock = null;

                    if ($item->variant_id) {
                        $variant = ProductVariant::where('id', $item->variant_id)
                            ->lockForUpdate()
                            ->first();
                        if (!$variant) {
                            throw new \Exception('Variant not found: ' . $item->variant_id);
                        }
                        $availableStock = $variant->stock;
                    } else {
                        $availableStock = $product->stock;
                    }

                    if ($availableStock !== null && $item->quantity > $availableStock) {
                        $itemName = $product->name . ($variant ? ' - ' . $variant->color : '');
                        throw new \Exception('Insufficient stock for product: ' . $itemName);
                    }

                    $OrderProduct = new OrderProduct;
                    $OrderProduct->order_id = $order->id;
                    $OrderProduct->product_id = $item->product_id;
                    $OrderProduct->variant_id = $item->variant_id;
                    $OrderProduct->quantity = $item->quantity;
                    $OrderProduct->price = $item->price;

                    if ($variant) {
                        $OrderProduct->color = $variant->color;
                        $OrderProduct->color_code = $variant->color_code;
                    }

                    $OrderProduct->save();

                    if ($availableStock !== null) {
                        $decrement = min($item->quantity, $availableStock);
                        if ($decrement > 0) {
                            if ($variant) {
                                $variant->decrement('stock', $decrement);
                            } else {
                                $product->decrement('stock', $decrement);
                            }
                        }
                    }

                    $total += $item->quantity * $item->price;
                }

                Cart::where('user_id', $request->user()->id)
                    ->where('is_selected', true)
                    ->delete();

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
        $orders = Order::with([
            'orderProducts.product.images',
            'orderProducts.variant.images'
        ])->where('user_id', $request->user()->id)->get();

        return $this->success('Get orders', $orders);
    }

    public function getAdminOrders(Request $request)
    {
        $orders = Order::with([
            'orderProducts.product.images',
            'orderProducts.variant.images',
            'user'
        ])->get();

        return $this->success('Get orders', $orders);
    }

    public function getAdminOrder(Request $request, $id)
    {
        $order = Order::with([
            'orderProducts.product.images',
            'orderProducts.variant.images',
            'user'
        ])->where('id', $id)->first();

        if (!$order) {
            return $this->error('Order not found', 404);
        }

        return $this->success('Get single order', $order);
    }

    public function updateAdminOrder(Request $request)
    {
        $order = Order::find($request->id);
        if (!$order) {
            return $this->error('Order not found', 404);
        }

        $order->status = $request->status;
        $order->notes = $request->notes;
        $order->save();

        $products = $request->products;
        if ($products) {
            foreach ($products as $product) {
                $OrderProduct = OrderProduct::where('order_id', $order->id)
                    ->where('product_id', $product['id'])
                    ->first();
                if ($OrderProduct) {
                    $OrderProduct->quantity = $product['pivot']['quantity'];
                    $OrderProduct->save();
                }
            }
        }

        return $this->success('Update order', $order);
    }

    public function directOrder(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'line1' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
        ]);

        $product = Product::where('id', $request->product_id)->first();
        if (!$product) {
            return $this->error('Product not found', 404);
        }

        $variant = null;
        $price = $request->price ?? $product->offer_price ?? $product->price;
        $availableStock = null;

        if ($request->variant_id) {
            $variant = ProductVariant::where('id', $request->variant_id)
                ->where('product_id', $request->product_id)
                ->first();

            if (!$variant) {
                return $this->error('Variant not found', 404);
            }

            $price = $variant->offer_price ?? $variant->price;
            $availableStock = $variant->stock;
        } else {
            $availableStock = $product->stock;
        }

        if ($availableStock !== null && $request->quantity > $availableStock) {
            $itemName = $product->name . ($variant ? ' - ' . $variant->color : '');
            return $this->error('Insufficient stock for product: ' . $itemName, 400);
        }

        try {
            $order = DB::transaction(function () use ($request, $product, $variant, $price) {
                if ($variant) {
                    $variant = ProductVariant::where('id', $variant->id)->lockForUpdate()->first();
                } else {
                    $product = Product::where('id', $product->id)->lockForUpdate()->first();
                }

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
                $OrderProduct->variant_id = $request->variant_id ?? null;
                $OrderProduct->quantity = $request->quantity;
                $OrderProduct->price = $price;

                if ($variant) {
                    $OrderProduct->color = $variant->color;
                    $OrderProduct->color_code = $variant->color_code;
                }

                $OrderProduct->save();

                $availableStock = $variant ? $variant->stock : $product->stock;
                if ($availableStock !== null) {
                    $decrement = min($request->quantity, $availableStock);
                    if ($decrement > 0) {
                        if ($variant) {
                            $variant->decrement('stock', $decrement);
                        } else {
                            $product->decrement('stock', $decrement);
                        }
                    }
                }

                $total = $request->quantity * $price;
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