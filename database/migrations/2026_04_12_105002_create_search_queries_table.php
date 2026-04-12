<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->string('query');
            $table->integer('results_count')->default(0);
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['store_id', 'created_at'], 'idx_search_queries_store_created');
            $table->index('customer_id', 'idx_search_queries_customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
