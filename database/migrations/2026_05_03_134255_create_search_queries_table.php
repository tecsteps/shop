<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->text('query');
            $table->json('filters_json')->nullable();
            $table->integer('results_count')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index('store_id');
            $table->index(['store_id', 'created_at'], 'search_queries_store_created_index');
            $table->index(['store_id', 'query'], 'search_queries_store_query_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
