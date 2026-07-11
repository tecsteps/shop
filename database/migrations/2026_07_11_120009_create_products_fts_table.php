<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
                tags
            )
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS products_fts');
    }
};
