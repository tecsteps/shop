<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_option_values', function (Blueprint $table) {
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained()->cascadeOnDelete();

            $table->primary(['variant_id', 'product_option_value_id']);
            $table->index('product_option_value_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_option_values');
    }
};
