<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE VIRTUAL TABLE products_fts USING fts5(title, description, vendor, product_type, tags)');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS products_fts');
    }
};
