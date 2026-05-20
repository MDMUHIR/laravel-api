<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
        'total',
        'is_selected',
    ];

    protected $attributes = [
        'is_selected' => false,
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    protected $casts = [
        'is_selected' => 'boolean',
    ];

    public function getEffectivePriceAttribute()
    {
        if ($this->variant && $this->variant->offer_price) {
            return $this->variant->offer_price;
        }
        if ($this->variant) {
            return $this->variant->price;
        }
        return $this->price;
    }

    public function getEffectiveStockAttribute()
    {
        if ($this->variant) {
            return $this->variant->stock;
        }
        return $this->product->stock;
    }
}
