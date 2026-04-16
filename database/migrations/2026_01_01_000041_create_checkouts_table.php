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
            $table->string('token', 64)->unique();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('started');
            $table->string('payment_method')->nullable();
            $table->string('email')->nullable();
            $table->text('shipping_address_json')->nullable();
            $table->text('billing_address_json')->nullable();
            $table->unsignedBigInteger('shipping_method_id')->nullable();
            $table->string('discount_code')->nullable();
            $table->text('tax_provider_snapshot_json')->nullable();
            $table->text('totals_json')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status'], 'idx_checkouts_store_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};
