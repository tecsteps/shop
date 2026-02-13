<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained('product_options')->cascadeOnDelete();
            $table->string('value');
            $table->integer('position')->default(0);

            $table->index('product_option_id');
            $table->unique(['product_option_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_values');
    }
};
