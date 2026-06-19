<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variant_images', function (Blueprint $table) {
            if (!Schema::hasColumn('variant_images', 'url')) {
                $table->string('url')->after('variant_id');
            }
            if (!Schema::hasColumn('variant_images', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('url');
            }
        });

        DB::table('variant_images')->whereNotNull('image_path')->update([
            'url' => DB::raw('image_path')
        ]);

        Schema::table('variant_images', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('variant_images', function (Blueprint $table) {
            $table->string('image_path')->after('variant_id');
        });

        DB::table('variant_images')->whereNotNull('url')->update([
            'image_path' => DB::raw('url')
        ]);

        Schema::table('variant_images', function (Blueprint $table) {
            $table->dropColumn('url');
            $table->dropColumn('is_featured');
        });
    }
};
