<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('position')->default(0);

            $table->index('product_id', 'idx_product_options_product_id');
            $table->unique(['product_id', 'position'], 'idx_product_options_product_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};
