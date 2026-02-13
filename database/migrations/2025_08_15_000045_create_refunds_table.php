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
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->integer('amount')->default(0);
            $table->text('reason')->nullable();
            $table->text('status')->default('pending')->check("status IN ('pending','processed','failed')");
            $table->text('provider_refund_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_refunds_order_id');
            $table->index('payment_id', 'idx_refunds_payment_id');
            $table->index('status', 'idx_refunds_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
