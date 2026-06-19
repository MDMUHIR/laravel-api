<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (!Schema::hasColumn('product_images', 'url')) {
                $table->string('url')->after('product_id');
            }
            if (!Schema::hasColumn('product_images', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('url');
            }
        });

        DB::statement('UPDATE product_images SET url = image_path WHERE url IS NULL');

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn('image_path');
            $table->dropColumn('color');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('image_path')->after('product_id');
            $table->string('color')->nullable()->after('image_path');
        });

        DB::statement('UPDATE product_images SET image_path = url WHERE image_path IS NULL');

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn('url');
            $table->dropColumn('is_featured');
        });
    }
};
