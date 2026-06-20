<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'name',
        'slug',
        'sku',
        'image',
        'attributes',
        'original_price',
        'unit_price',
        'discount',
        'quantity',
        'price',
        'line_total',
        'stock_snapshot',
    ];

    protected $casts = [
        'attributes' => 'array',
        'stock_snapshot' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
