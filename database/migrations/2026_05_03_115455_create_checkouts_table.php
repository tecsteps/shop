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
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->enum('status', ['started', 'addressed', 'shipping_selected', 'payment_selected', 'completed', 'expired'])->default('started');
            $table->enum('payment_method', ['credit_card', 'paypal', 'bank_transfer'])->nullable();
            $table->string('email')->nullable();
            $table->text('shipping_address_json')->nullable();
            $table->text('billing_address_json')->nullable();
            $table->unsignedBigInteger('shipping_method_id')->nullable();
            $table->string('discount_code')->nullable();
            $table->text('tax_provider_snapshot_json')->nullable();
            $table->text('totals_json')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('store_id');
            $table->index('cart_id');
            $table->index('customer_id');
            $table->index(['store_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};
