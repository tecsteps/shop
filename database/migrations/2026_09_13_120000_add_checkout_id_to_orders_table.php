<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links orders to their source checkout so completeCheckout can stay
     * idempotent per spec 05 section 6.2 ("IF order already exists for
     * this checkout: RETURN existing order").
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('checkout_id')->nullable()->after('store_id')->constrained('checkouts')->nullOnDelete();
            $table->index('checkout_id', 'idx_orders_checkout_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['checkout_id']);
            $table->dropIndex('idx_orders_checkout_id');
            $table->dropColumn('checkout_id');
        });
    }
};
