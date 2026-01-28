<?php

namespace App\Http\Controllers;

use App\Models\WishList;
use Illuminate\Http\Request;

class WishListController extends Controller
{
    public function getWishList(Request $request)
    {
        $wishlist = WishList::with('product.images')->where('user_id', $request->user()->id)->get();

        return $this->success('Get wishlist', $wishlist);
    }

    public function addToWishList(Request $request)
    {

        $wishlist = new WishList;
        $wishlist->user_id = $request->user()->id;
        $wishlist->product_id = $request->product_id;
        $wishlist->save();

        return $this->success('Add to wishlist', $wishlist);
    }

    public function deleteWishList(Request $request, $id)
    {
        // return $request->user()->id.">".$id;

        $wishlist = WishList::where('user_id', $request->user()->id)->where('id', $id)->first();

        // if(!$wishlist){
        // return $this->success('Wishlist Product not Found', null);}

        $wishlist->delete();

        return $this->success('Delete wishlist', $wishlist);
    }
}
