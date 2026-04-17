<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->integer('customer_id')->nullable();
            $table->text('status')->default('started');
            $table->text('payment_method')->nullable();
            $table->text('email')->nullable();
            $table->text('shipping_address_json')->nullable();
            $table->text('billing_address_json')->nullable();
            $table->integer('shipping_method_id')->nullable();
            $table->text('discount_code')->nullable();
            $table->text('tax_provider_snapshot_json')->nullable();
            $table->text('totals_json')->nullable();
            $table->text('expires_at')->nullable();
            $table->timestamps();

            $table->index('store_id', 'idx_checkouts_store_id');
            $table->index('cart_id', 'idx_checkouts_cart_id');
            $table->index('customer_id', 'idx_checkouts_customer_id');
            $table->index(['store_id', 'status'], 'idx_checkouts_status');
            $table->index('expires_at', 'idx_checkouts_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};
