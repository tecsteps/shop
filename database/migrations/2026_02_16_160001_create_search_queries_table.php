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
            $table->text('query');
            $table->integer('results_count')->default(0);
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->text('session_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
