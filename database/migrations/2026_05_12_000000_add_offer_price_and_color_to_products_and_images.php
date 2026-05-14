<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('offer_price', 8, 2)->nullable()->after('price');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->string('color')->nullable()->after('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('offer_price');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
