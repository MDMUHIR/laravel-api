<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('carts')->whereNull('is_selected')->update(['is_selected' => false]);
    }

    public function down(): void
    {
    }
};
