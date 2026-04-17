<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_products', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained('collections')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('position')->default(0);

            $table->primary(['collection_id', 'product_id']);
            $table->index('product_id');
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_products');
    }
};
