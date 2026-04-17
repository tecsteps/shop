<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE VIRTUAL TABLE IF NOT EXISTS products_fts USING fts5(title, description, vendor, product_type, tags, content="", content_rowid="rowid")');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS products_fts');
    }
};
