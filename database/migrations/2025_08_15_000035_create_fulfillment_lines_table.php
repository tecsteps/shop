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

            $table->index('fulfillment_id');
            $table->index('order_line_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillment_lines');
    }
};
