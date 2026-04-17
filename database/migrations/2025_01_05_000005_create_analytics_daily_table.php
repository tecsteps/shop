<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id');
            $table->string('date');
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('revenue_amount')->default(0);
            $table->unsignedInteger('aov_amount')->default(0);
            $table->unsignedInteger('visits_count')->default(0);
            $table->unsignedInteger('add_to_cart_count')->default(0);
            $table->unsignedInteger('checkout_started_count')->default(0);
            $table->unsignedInteger('checkout_completed_count')->default(0);

            $table->primary(['store_id', 'date']);
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
            $table->index(['store_id', 'date'], 'idx_analytics_daily_store_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily');
    }
};
