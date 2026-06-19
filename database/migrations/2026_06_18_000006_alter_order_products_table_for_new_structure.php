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
            $table->json('variant_attributes')->nullable()->after('price');
        });

        $rows = DB::table('order_products')->whereNotNull('color')->orWhereNotNull('color_code')->get();
        foreach ($rows as $row) {
            $attrs = [];
            if ($row->color) {
                $attrs[] = ['attribute' => 'Color', 'value' => $row->color];
            }
            if ($row->color_code) {
                $attrs[] = ['attribute' => 'Color Code', 'value' => $row->color_code];
            }
            if (!empty($attrs)) {
                DB::table('order_products')->where('id', $row->id)->update([
                    'variant_attributes' => json_encode($attrs),
                ]);
            }
        }

        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn(['color', 'color_code']);
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->string('color')->nullable()->after('variant_id');
            $table->string('color_code')->nullable()->after('color');
        });

        DB::table('order_products')->whereNotNull('variant_attributes')->get()->each(function ($row) {
            $attrs = json_decode($row->variant_attributes, true) ?? [];
            $color = null;
            $colorCode = null;
            foreach ($attrs as $attr) {
                if (strtolower($attr['attribute'] ?? '') === 'color') {
                    $color = $attr['value'] ?? null;
                }
                if (strtolower($attr['attribute'] ?? '') === 'color code') {
                    $colorCode = $attr['value'] ?? null;
                }
            }
            DB::table('order_products')->where('id', $row->id)->update([
                'color' => $color,
                'color_code' => $colorCode,
            ]);
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('variant_attributes');
        });
    }
};
