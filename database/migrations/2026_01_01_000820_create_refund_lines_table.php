<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('refund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->integer('amount')->default(0);
            $table->unique(['refund_id', 'order_line_id']);
            $table->index('order_line_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_lines');
    }
};
