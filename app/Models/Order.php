<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'payment_method',
        'payment_status',
        'name',
        'phone',
        'phone_alt',
        'email',
        'line1',
        'line2',
        'city',
        'country',
        'coupon',
        'total',
        'notes',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_products', 'order_id', 'product_id')->withPivot('quantity', 'price')->with('images');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
