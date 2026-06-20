<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'payment_method',
        'payment_status',
        'name',
        'email',
        'phone',
        'phone_alt',
        'line1',
        'line2',
        'district',
        'city',
        'country',
        'currency',
        'subtotal',
        'delivery_charge',
        'discount',
        'discount_type',
        'coupon_code',
        'shipping_method',
        'estimated_delivery_days',
        'total_items',
        'total_quantity',
        'total',
        'notes',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_products', 'order_id', 'product_id')
            ->withPivot('quantity', 'price', 'variant_id', 'attributes')
            ->with('images');
    }

    public function items()
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
