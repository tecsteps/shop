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
        DB::statement("
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
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_fts');
    }
};
