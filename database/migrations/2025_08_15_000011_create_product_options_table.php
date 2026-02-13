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
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name');
            $table->integer('position')->default(0);

            $table->index('product_id');
            $table->unique(['product_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};
