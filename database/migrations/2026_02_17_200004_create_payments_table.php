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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('mock');
            $table->string('method');
            $table->string('provider_payment_id')->nullable();
            $table->string('status')->default('pending');
            $table->integer('amount')->default(0);
            $table->string('currency')->default('EUR');
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();
            $table->text('raw_json_encrypted')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_payments_order_id');
            $table->index(['provider', 'provider_payment_id'], 'idx_payments_provider_id');
            $table->index('method', 'idx_payments_method');
            $table->index('status', 'idx_payments_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
