<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fulfillment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fulfillment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained('order_lines')->cascadeOnDelete();
            $table->integer('quantity')->default(1);

            $table->index('fulfillment_id', 'idx_fulfillment_lines_fulfillment_id');
            $table->unique(['fulfillment_id', 'order_line_id'], 'idx_fulfillment_lines_fulfillment_order_line');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillment_lines');
    }
};
