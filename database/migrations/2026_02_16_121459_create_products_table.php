<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('description_html')->nullable();
            $table->string('status')->default('draft');
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();
            $table->json('tags')->default('[]');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'handle']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'published_at']);
            $table->index(['store_id', 'vendor']);
            $table->index(['store_id', 'product_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
