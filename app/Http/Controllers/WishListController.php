<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WishList;
use Illuminate\Http\Request;

class WishListController extends Controller
{
    public function getWishList(Request $request)
    {
        $wishlist = WishList::with([
            'product.images',
            'variant.images'
        ])->where('user_id', $request->user()->id)->get();

        $wishlist = $wishlist->map(function ($item) {
            $item->effective_price = null;
            $item->effective_stock = null;

            if ($item->variant) {
                $item->effective_price = $item->variant->offer_price ?? $item->variant->price;
                $item->effective_stock = $item->variant->stock;
            } elseif ($item->product) {
                $item->effective_price = $item->product->offer_price ?? $item->product->price;
                $item->effective_stock = $item->product->stock;
            }

            return $item;
        });

        return $this->success('Get wishlist', $wishlist);
    }

    public function addToWishList(Request $request)
    {
        $this->validate($request, [
            'product_id' => 'required|integer',
        ]);

        $product = Product::find($request->product_id);
        if (!$product) {
            return $this->error('Product not found', 404);
        }

        if ($request->variant_id) {
            $variant = ProductVariant::where('id', $request->variant_id)
                ->where('product_id', $request->product_id)
                ->first();

            if (!$variant) {
                return $this->error('Variant not found', 404);
            }

            $exists = WishList::where('user_id', $request->user()->id)
                ->where('product_id', $request->product_id)
                ->where('variant_id', $request->variant_id)
                ->first();

            if ($exists) {
                return $this->error('Item already in wishlist', 400);
            }
        } else {
            $exists = WishList::where('user_id', $request->user()->id)
                ->where('product_id', $request->product_id)
                ->whereNull('variant_id')
                ->first();

            if ($exists) {
                return $this->error('Item already in wishlist', 400);
            }
        }

        $wishlist = new WishList;
        $wishlist->user_id = $request->user()->id;
        $wishlist->product_id = $request->product_id;
        $wishlist->variant_id = $request->variant_id ?? null;
        $wishlist->save();

        return $this->success('Add to wishlist', $wishlist);
    }

    public function deleteWishList(Request $request, $id)
    {
        $wishlist = WishList::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$wishlist) {
            return $this->error('Wishlist item not found', 404);
        }

        $wishlist->delete();

        return $this->success('Delete wishlist', null);
    }
}
