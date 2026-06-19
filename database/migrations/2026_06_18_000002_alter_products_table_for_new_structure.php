<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'status_string')) {
                $table->string('status_string', 50)->nullable()->after('status');
            }
        });

        DB::table('products')->whereNotNull('status')->update([
            'status_string' => DB::raw('CASE WHEN status = 1 OR status = true THEN "active" ELSE "discontinued" END')
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('image');
            $table->dropColumn('has_variants');
            $table->dropColumn('default_variant_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('status_string', 'status');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('status')->default(true)->after('offer_price');
            $table->string('image')->nullable()->after('stock');
            $table->boolean('has_variants')->default(false)->after('stock');
            $table->foreignId('default_variant_id')->nullable()->after('has_variants');
            $table->foreignId('category_id')->nullable(false)->change();
        });

        DB::table('products')->whereIn('status', ['active', 'draft', 'published'])->update(['status' => true]);
        DB::table('products')->whereNotIn('status', ['active', 'draft', 'published'])->update(['status' => false]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
