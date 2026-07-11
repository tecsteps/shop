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
        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedBigInteger('revenue_amount')->default(0);
            $table->unsignedBigInteger('aov_amount')->default(0);
            $table->unsignedInteger('visits_count')->default(0);
            $table->unsignedInteger('add_to_cart_count')->default(0);
            $table->unsignedInteger('checkout_started_count')->default(0);
            $table->unsignedInteger('checkout_completed_count')->default(0);

            $table->primary(['store_id', 'date']);
            $table->index(['store_id', 'date'], 'idx_analytics_daily_store_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_daily');
    }
};
