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
            $table->string('query');
            $table->json('filters_json')->nullable();
            $table->integer('results_count')->default(0);
            $table->timestamps();
            $table->index('store_id');
            $table->index(['store_id', 'created_at']);
            $table->index(['store_id', 'query']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
