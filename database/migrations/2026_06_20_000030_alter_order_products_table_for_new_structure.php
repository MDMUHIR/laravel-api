<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            if (!Schema::hasColumn('order_products', 'name')) {
                $table->string('name')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('order_products', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('order_products', 'sku')) {
                $table->string('sku')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('order_products', 'image')) {
                $table->string('image')->nullable()->after('sku');
            }
            if (!Schema::hasColumn('order_products', 'original_price')) {
                $table->decimal('original_price', 10, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('order_products', 'unit_price')) {
                $table->decimal('unit_price', 10, 2)->nullable()->after('original_price');
            }
            if (!Schema::hasColumn('order_products', 'discount')) {
                $table->decimal('discount', 10, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('order_products', 'line_total')) {
                $table->decimal('line_total', 10, 2)->nullable()->after('discount');
            }
            if (!Schema::hasColumn('order_products', 'stock_snapshot')) {
                $table->json('stock_snapshot')->nullable()->after('line_total');
            }
        });

        if (Schema::hasColumn('order_products', 'variant_attributes')) {
            Schema::table('order_products', function (Blueprint $table) {
                $table->renameColumn('variant_attributes', 'attributes');
            });
        }
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn(['name', 'slug', 'sku', 'image', 'original_price', 'unit_price', 'line_total', 'stock_snapshot']);
        });

        if (Schema::hasColumn('order_products', 'attributes')) {
            Schema::table('order_products', function (Blueprint $table) {
                $table->renameColumn('attributes', 'variant_attributes');
            });
        }
    }
};
