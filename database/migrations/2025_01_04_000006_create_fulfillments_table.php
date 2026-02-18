<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->text('status')->default('pending');
            $table->text('tracking_company')->nullable();
            $table->text('tracking_number')->nullable();
            $table->text('tracking_url')->nullable();
            $table->text('shipped_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_fulfillments_order_id');
            $table->index('status', 'idx_fulfillments_status');
            $table->index(['tracking_company', 'tracking_number'], 'idx_fulfillments_tracking');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillments');
    }
};
