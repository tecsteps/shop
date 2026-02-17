<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->integer('amount')->default(0);
            $table->string('currency')->default('EUR');
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('restock')->default(false);
            $table->string('provider_refund_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_refunds_order_id');
            $table->index('payment_id', 'idx_refunds_payment_id');
            $table->index('status', 'idx_refunds_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
