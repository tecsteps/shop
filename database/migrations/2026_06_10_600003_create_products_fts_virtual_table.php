<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Create the products_fts FTS5 virtual table (spec 05 section 16.1).
     *
     * The table mirrors searchable product data and uses the product id as
     * its rowid so index rows can be replaced and removed cheaply. store_id
     * and product_id are UNINDEXED: they filter results but are not part of
     * the full-text index. Raw SQL is required because the schema builder
     * cannot create virtual tables; the statement is idempotent so reruns
     * (e.g. RefreshDatabase against a persistent test database) are safe.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement(<<<'SQL'
            CREATE VIRTUAL TABLE IF NOT EXISTS products_fts USING fts5(
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
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS products_fts');
    }
};
