<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS products_fts');
        DB::statement("CREATE VIRTUAL TABLE products_fts USING fts5(store_id UNINDEXED, product_id UNINDEXED, title, description, vendor, product_type, tags, tokenize='unicode61')");

        foreach (DB::table('products')->orderBy('id')->cursor() as $product) {
            DB::table('products_fts')->insert([
                'store_id' => (string) $product->store_id,
                'product_id' => (string) $product->id,
                'title' => $product->title,
                'description' => strip_tags((string) $product->description_html),
                'vendor' => (string) $product->vendor,
                'product_type' => (string) $product->product_type,
                'tags' => implode(' ', json_decode((string) $product->tags, true) ?: []),
            ]);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('DROP TABLE IF EXISTS products_fts');
        DB::statement("CREATE VIRTUAL TABLE products_fts USING fts5(store_id UNINDEXED, product_id UNINDEXED, title, description, vendor, tags, tokenize='unicode61')");

        foreach (DB::table('products')->orderBy('id')->cursor() as $product) {
            DB::table('products_fts')->insert([
                'store_id' => (string) $product->store_id,
                'product_id' => (string) $product->id,
                'title' => $product->title,
                'description' => strip_tags((string) $product->description_html),
                'vendor' => (string) $product->vendor,
                'tags' => implode(' ', json_decode((string) $product->tags, true) ?: []),
            ]);
        }
    }
};
