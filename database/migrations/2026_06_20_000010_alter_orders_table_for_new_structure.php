<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_number')) {
                $table->string('order_number')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('orders', 'district')) {
                $table->string('district')->nullable()->after('line2');
            }
            if (!Schema::hasColumn('orders', 'currency')) {
                $table->string('currency', 10)->default('BDT')->after('country');
            }
            if (!Schema::hasColumn('orders', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->nullable()->after('currency');
            }
            if (!Schema::hasColumn('orders', 'delivery_charge')) {
                $table->decimal('delivery_charge', 10, 2)->nullable()->after('subtotal');
            }
            if (!Schema::hasColumn('orders', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0)->after('delivery_charge');
            }
            if (!Schema::hasColumn('orders', 'discount_type')) {
                $table->string('discount_type')->nullable()->after('discount');
            }
            if (!Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code')->nullable()->after('discount_type');
            }
            if (!Schema::hasColumn('orders', 'shipping_method')) {
                $table->string('shipping_method')->nullable()->after('coupon_code');
            }
            if (!Schema::hasColumn('orders', 'estimated_delivery_days')) {
                $table->integer('estimated_delivery_days')->nullable()->after('shipping_method');
            }
            if (!Schema::hasColumn('orders', 'total_items')) {
                $table->integer('total_items')->default(0)->after('estimated_delivery_days');
            }
            if (!Schema::hasColumn('orders', 'total_quantity')) {
                $table->integer('total_quantity')->default(0)->after('total_items');
            }
        });

        DB::statement('UPDATE orders SET coupon_code = coupon WHERE coupon_code IS NULL');
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'order_number', 'district', 'currency', 'subtotal',
                'delivery_charge', 'discount', 'discount_type', 'coupon_code',
                'shipping_method', 'estimated_delivery_days', 'total_items', 'total_quantity',
            ]);
        });
    }
};
