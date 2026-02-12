<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if FTS5 extension is available
        try {
            DB::statement('CREATE VIRTUAL TABLE IF NOT EXISTS _fts5_check USING fts5(test)');
            DB::statement('DROP TABLE IF EXISTS _fts5_check');
        } catch (\Throwable) {
            // FTS5 is not available; skip this migration gracefully
            return;
        }

        // Create the FTS5 virtual table using external content from the products table
        DB::statement("
            CREATE VIRTUAL TABLE IF NOT EXISTS products_fts USING fts5(
                title,
                description,
                vendor,
                product_type,
                tags,
                content='products',
                content_rowid='id'
            )
        ");

        // Trigger: after inserting a new product, add it to the FTS index
        DB::statement("
            CREATE TRIGGER IF NOT EXISTS products_ai AFTER INSERT ON products BEGIN
                INSERT INTO products_fts(rowid, title, description, vendor, product_type, tags)
                VALUES (
                    new.id,
                    new.title,
                    COALESCE(REPLACE(REPLACE(new.description_html, '<', ' '), '>', ' '), ''),
                    COALESCE(new.vendor, ''),
                    COALESCE(new.product_type, ''),
                    COALESCE(new.tags, '[]')
                );
            END
        ");

        // Trigger: before updating a product, remove the old FTS entry
        DB::statement("
            CREATE TRIGGER IF NOT EXISTS products_ad AFTER DELETE ON products BEGIN
                INSERT INTO products_fts(products_fts, rowid, title, description, vendor, product_type, tags)
                VALUES (
                    'delete',
                    old.id,
                    old.title,
                    COALESCE(REPLACE(REPLACE(old.description_html, '<', ' '), '>', ' '), ''),
                    COALESCE(old.vendor, ''),
                    COALESCE(old.product_type, ''),
                    COALESCE(old.tags, '[]')
                );
            END
        ");

        // Trigger: on update, remove old entry and insert new entry
        DB::statement("
            CREATE TRIGGER IF NOT EXISTS products_au AFTER UPDATE ON products BEGIN
                INSERT INTO products_fts(products_fts, rowid, title, description, vendor, product_type, tags)
                VALUES (
                    'delete',
                    old.id,
                    old.title,
                    COALESCE(REPLACE(REPLACE(old.description_html, '<', ' '), '>', ' '), ''),
                    COALESCE(old.vendor, ''),
                    COALESCE(old.product_type, ''),
                    COALESCE(old.tags, '[]')
                );
                INSERT INTO products_fts(rowid, title, description, vendor, product_type, tags)
                VALUES (
                    new.id,
                    new.title,
                    COALESCE(REPLACE(REPLACE(new.description_html, '<', ' '), '>', ' '), ''),
                    COALESCE(new.vendor, ''),
                    COALESCE(new.product_type, ''),
                    COALESCE(new.tags, '[]')
                );
            END
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS products_au');
        DB::statement('DROP TRIGGER IF EXISTS products_ad');
        DB::statement('DROP TRIGGER IF EXISTS products_ai');
        DB::statement('DROP TABLE IF EXISTS products_fts');
    }
};
