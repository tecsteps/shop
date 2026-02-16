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
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->text('email')->nullable();
            $table->json('shipping_address_json')->nullable();
            $table->json('billing_address_json')->nullable();
            $table->integer('shipping_rate_id')->nullable();
            $table->text('shipping_method_name')->nullable();
            $table->integer('shipping_amount')->default(0);
            $table->text('discount_code')->nullable();
            $table->integer('discount_amount')->default(0);
            $table->text('payment_method')->nullable();
            $table->text('status')->default('started');
            $table->json('totals_json')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};
