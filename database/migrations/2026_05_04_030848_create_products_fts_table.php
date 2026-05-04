<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE VIRTUAL TABLE products_fts USING fts5(
                store_id UNINDEXED,
                product_id UNINDEXED,
                title,
                description,
                vendor,
                product_type,
                tags,
                tokenize = 'unicode61'
            )
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO products_fts (store_id, product_id, title, description, vendor, product_type, tags)
            SELECT store_id, id, title, COALESCE(description_html, ''), COALESCE(vendor, ''), COALESCE(product_type, ''), COALESCE(tags, '')
            FROM products
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_fts');
    }
};
