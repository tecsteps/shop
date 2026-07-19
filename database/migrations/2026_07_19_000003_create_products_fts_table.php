<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the FTS5 full-text index over products (spec 05 §16.1). The
     * table is virtual, so the schema builder cannot express it — raw SQL.
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
