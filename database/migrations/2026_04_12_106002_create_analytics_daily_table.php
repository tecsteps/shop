<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_daily', function (Blueprint $table): void {
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->date('date');
            $table->integer('orders_count')->default(0);
            $table->integer('revenue_amount')->default(0);
            $table->integer('aov_amount')->default(0);
            $table->integer('visits_count')->default(0);
            $table->integer('add_to_cart_count')->default(0);
            $table->integer('checkout_started_count')->default(0);
            $table->integer('checkout_completed_count')->default(0);

            $table->primary(['store_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_daily');
    }
};
