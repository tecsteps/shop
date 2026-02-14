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
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('checkout_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('checkouts')
                ->nullOnDelete();

            $table->unique('checkout_id', 'uq_orders_checkout_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['checkout_id']);
            $table->dropUnique('uq_orders_checkout_id');
            $table->dropColumn('checkout_id');
        });
    }
};
