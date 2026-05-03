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
            $table->enum('provider', ['mock'])->default('mock');
            $table->enum('method', ['credit_card', 'paypal', 'bank_transfer']);
            $table->string('provider_payment_id')->nullable();
            $table->enum('status', ['pending', 'captured', 'failed', 'refunded'])->default('pending');
            $table->integer('amount')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('raw_json_encrypted')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id');
            $table->index(['provider', 'provider_payment_id']);
            $table->index('method');
            $table->index('status');
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
