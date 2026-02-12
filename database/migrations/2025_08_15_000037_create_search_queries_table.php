<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('query_text');
            $table->integer('results_count')->default(0);
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('store_id');
            $table->index(['store_id', 'query_text']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
