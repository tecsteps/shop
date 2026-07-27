<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Links an order back to the checkout it was created from. Required for
     * the idempotency guarantee of completeCheckout (spec 05 §6.2 step 1:
     * "IF order already exists for this checkout: RETURN existing order").
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('checkout_id')->nullable()->constrained()->nullOnDelete();
            $table->index('checkout_id', 'idx_orders_checkout_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_checkout_id');
            $table->dropConstrainedForeignId('checkout_id');
        });
    }
};
