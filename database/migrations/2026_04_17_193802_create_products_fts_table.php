<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            CREATE VIRTUAL TABLE products_fts USING fts5(
                store_id UNINDEXED,
                product_id UNINDEXED,
                title,
                description,
                vendor,
                product_type,
                tags,
                tokenize = "unicode61 remove_diacritics 2"
            )
        ');

        DB::statement('
            CREATE TRIGGER products_ai_fts AFTER INSERT ON products BEGIN
                INSERT INTO products_fts (store_id, product_id, title, description, vendor, product_type, tags)
                VALUES (
                    NEW.store_id,
                    NEW.id,
                    COALESCE(NEW.title, ""),
                    COALESCE(REPLACE(REPLACE(REPLACE(NEW.description_html, "<", " <"), ">", "> "), "  ", " "), ""),
                    COALESCE(NEW.vendor, ""),
                    COALESCE(NEW.product_type, ""),
                    COALESCE(NEW.tags, "")
                );
            END
        ');

        DB::statement('
            CREATE TRIGGER products_ad_fts AFTER DELETE ON products BEGIN
                DELETE FROM products_fts WHERE product_id = OLD.id;
            END
        ');

        DB::statement('
            CREATE TRIGGER products_au_fts AFTER UPDATE ON products BEGIN
                DELETE FROM products_fts WHERE product_id = OLD.id;
                INSERT INTO products_fts (store_id, product_id, title, description, vendor, product_type, tags)
                VALUES (
                    NEW.store_id,
                    NEW.id,
                    COALESCE(NEW.title, ""),
                    COALESCE(REPLACE(REPLACE(REPLACE(NEW.description_html, "<", " <"), ">", "> "), "  ", " "), ""),
                    COALESCE(NEW.vendor, ""),
                    COALESCE(NEW.product_type, ""),
                    COALESCE(NEW.tags, "")
                );
            END
        ');

        DB::statement('
            INSERT INTO products_fts (store_id, product_id, title, description, vendor, product_type, tags)
            SELECT
                store_id,
                id,
                COALESCE(title, ""),
                COALESCE(REPLACE(REPLACE(REPLACE(description_html, "<", " <"), ">", "> "), "  ", " "), ""),
                COALESCE(vendor, ""),
                COALESCE(product_type, ""),
                COALESCE(tags, "")
            FROM products
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS products_ai_fts');
        DB::statement('DROP TRIGGER IF EXISTS products_ad_fts');
        DB::statement('DROP TRIGGER IF EXISTS products_au_fts');
        DB::statement('DROP TABLE IF EXISTS products_fts');
    }
};
