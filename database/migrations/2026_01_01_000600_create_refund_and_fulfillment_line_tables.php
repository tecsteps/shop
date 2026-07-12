<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->integer('amount')->default(0);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->string('provider_refund_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index('order_id', 'idx_refunds_order_id');
            $table->index('payment_id', 'idx_refunds_payment_id');
            $table->index('status', 'idx_refunds_status');
        });

        Schema::create('fulfillment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fulfillment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->index('fulfillment_id', 'idx_fulfillment_lines_fulfillment_id');
            $table->unique(['fulfillment_id', 'order_line_id'], 'idx_fulfillment_lines_fulfillment_order_line');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillment_lines');
        Schema::dropIfExists('refunds');
    }
};
