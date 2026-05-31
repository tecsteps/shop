<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the `products_fts` SQLite FTS5 virtual table backing product
     * full-text search. The index is kept in sync explicitly by
     * {@see \App\Services\SearchService} via {@see \App\Observers\ProductObserver},
     * so no content-table linkage or triggers are used. `rowid` mirrors the
     * product id; `store_id` is an UNINDEXED column used only to scope queries.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement(<<<'SQL'
            CREATE VIRTUAL TABLE IF NOT EXISTS products_fts USING fts5(
                title,
                description,
                vendor,
                product_type,
                tags,
                store_id UNINDEXED
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
