<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('app_installation_id')->nullable()->constrained('app_installations');
            $table->text('event_type');
            $table->text('target_url');
            $table->text('secret');
            $table->text('status')->default('active');
            $table->integer('consecutive_failures')->default(0);
            $table->timestamps();

            $table->index(['store_id', 'event_type'], 'idx_webhook_subscriptions_store_event');
            $table->index('status', 'idx_webhook_subscriptions_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
