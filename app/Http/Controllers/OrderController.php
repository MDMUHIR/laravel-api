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
        $cart = Cart::with(['product', 'variant'])
            ->where('user_id', $request->user()->id)
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
                    if (! $product) {
                        throw new \Exception('Product not found: '.$item->product_id);
                    }

                    $variant = null;
                    $availableStock = null;

                    if ($item->variant_id) {
                        $variant = ProductVariant::where('id', $item->variant_id)
                            ->lockForUpdate()
                            ->first();
                        if (! $variant) {
                            throw new \Exception('Variant not found: '.$item->variant_id);
                        }
                        $availableStock = $variant->stock;
                    } else {
                        $availableStock = $product->stock;
                    }

                    if ($availableStock !== null && $item->quantity > $availableStock) {
                        $itemName = $product->name.($variant ? ' ('.$variant->sku.')' : '');
                        throw new \Exception('Insufficient stock for product: '.$itemName);
                    }

                    $variantAttributes = null;
                    if ($variant) {
                        $variantAttributes = $variant->attributes->map(function ($attr) {
                            return ['attribute' => $attr->attribute, 'value' => $attr->value];
                        })->toArray();
                    }

                    $OrderProduct = new OrderProduct;
                    $OrderProduct->order_id = $order->id;
                    $OrderProduct->product_id = $item->product_id;
                    $OrderProduct->variant_id = $item->variant_id;
                    $OrderProduct->quantity = $item->quantity;
                    $OrderProduct->price = $item->price;
                    $OrderProduct->variant_attributes = $variantAttributes;
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
            'orderProducts.variant.images',
            'orderProducts.variant.attributes',
        ])->where('user_id', $request->user()->id)->get();

        return $this->success('Get orders', $orders);
    }

    public function getAdminOrders(Request $request)
    {
        $orders = Order::with([
            'orderProducts.product.images',
            'orderProducts.variant.images',
            'orderProducts.variant.attributes',
            'user',
        ])->get();

        return $this->success('Get orders', $orders);
    }

    public function getAdminOrder(Request $request, $id)
    {
        $order = Order::with([
            'orderProducts.product.images',
            'orderProducts.variant.images',
            'orderProducts.variant.attributes',
            'user',
        ])->where('id', $id)->first();

        if (! $order) {
            return $this->error('Order not found', 404);
        }

        return $this->success('Get single order', $order);
    }

    public function updateAdminOrder(Request $request)
    {
        $order = Order::find($request->id);
        if (! $order) {
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
        $items = $request->items;

        if (! $items || ! is_array($items)) {
            $items = [[
                'product_id' => $request->product_id,
                'variant_id' => $request->variant_id ?? null,
                'quantity' => $request->quantity,
            ]];
        }

        if (empty($items)) {
            return $this->error('No items provided', 400);
        }

        $request->validate([
            'payment_method' => 'required|string',
            'name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'line1' => 'required|string',
            'city' => 'required|string',
            'country' => 'required|string',
        ]);

        $errors = [];
        $validatedItems = [];

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
            $availableStock = null;

            if ($variantId) {
                $variant = ProductVariant::where('id', $variantId)
                    ->where('product_id', $productId)
                    ->with('attributes')
                    ->first();

                if (! $variant) {
                    $errors[] = 'Item '.($index + 1).': Variant not found';

                    continue;
                }

                $price = $price ?? ($variant->offer_price ?? $variant->price);
                $availableStock = $variant->stock;
            } else {
                if ($product->variants()->exists()) {
                    $errors[] = 'Item '.($index + 1).': Please select a variant';

                    continue;
                }
                $price = $price ?? ($product->offer_price ?? $product->price);
                $availableStock = $product->stock;
            }

            if ($availableStock !== null && $quantity > $availableStock) {
                $errors[] = 'Item '.($index + 1).': Insufficient stock';

                continue;
            }

            $validatedItems[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'price' => $price,
            ];
        }

        if (empty($validatedItems) && ! empty($errors)) {
            return $this->error(implode('. ', $errors), 400);
        }

        try {
            $order = DB::transaction(function () use ($request, $validatedItems) {
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

                $total = 0;

                foreach ($validatedItems as $item) {
                    $product = Product::where('id', $item['product']->id)->lockForUpdate()->first();

                    $variant = null;
                    $availableStock = null;

                    if ($item['variant']) {
                        $variant = ProductVariant::where('id', $item['variant']->id)->lockForUpdate()->first();
                        $availableStock = $variant ? $variant->stock : null;
                    } else {
                        $availableStock = $product ? $product->stock : null;
                    }

                    if ($availableStock !== null && $item['quantity'] > $availableStock) {
                        throw new \Exception('Insufficient stock for product: '.($product->name ?? ''));
                    }

                    $variantAttributes = null;
                    if ($item['variant']) {
                        $variantAttributes = $item['variant']->attributes->map(function ($attr) {
                            return ['attribute' => $attr->attribute, 'value' => $attr->value];
                        })->toArray();
                    }

                    $OrderProduct = new OrderProduct;
                    $OrderProduct->order_id = $order->id;
                    $OrderProduct->product_id = $item['product']->id;
                    $OrderProduct->variant_id = $item['variant'] ? $item['variant']->id : null;
                    $OrderProduct->quantity = $item['quantity'];
                    $OrderProduct->price = $item['price'];
                    $OrderProduct->variant_attributes = $variantAttributes;
                    $OrderProduct->save();

                    if ($availableStock !== null) {
                        $decrement = min($item['quantity'], $availableStock);
                        if ($decrement > 0) {
                            if ($variant) {
                                $variant->decrement('stock', $decrement);
                            } elseif ($product) {
                                $product->decrement('stock', $decrement);
                            }
                        }
                    }

                    $total += $item['quantity'] * $item['price'];
                }

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
