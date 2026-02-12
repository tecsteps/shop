<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('search_settings', function (Blueprint $table) {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->json('synonyms_json')->default('[]');
            $table->json('stop_words_json')->default('[]');
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('query');
            $table->json('filters_json')->nullable();
            $table->integer('results_count')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index('store_id', 'idx_search_queries_store_id');
            $table->index(['store_id', 'created_at'], 'idx_search_queries_store_created');
            $table->index(['store_id', 'query'], 'idx_search_queries_store_query');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE VIRTUAL TABLE products_fts USING fts5(
                store_id UNINDEXED,
                product_id UNINDEXED,
                title,
                description,
                vendor,
                product_type,
                tags
            )');

            return;
        }

        Schema::create('products_fts', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->text('vendor')->nullable();
            $table->text('product_type')->nullable();
            $table->text('tags')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP TABLE IF EXISTS products_fts');
        } else {
            Schema::dropIfExists('products_fts');
        }

        Schema::dropIfExists('search_queries');
        Schema::dropIfExists('search_settings');
    }
};
