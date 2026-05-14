<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'has_variants')) {
                $table->boolean('has_variants')->default(false)->after('stock');
            }
            if (!Schema::hasColumn('products', 'default_variant_id')) {
                $table->foreignId('default_variant_id')->nullable()->after('has_variants');
            }
            if (!Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('products', 'short_description')) {
                $table->text('short_description')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['has_variants', 'default_variant_id', 'slug', 'short_description']);
        });
    }
};
