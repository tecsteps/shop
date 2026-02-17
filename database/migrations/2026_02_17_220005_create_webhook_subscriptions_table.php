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
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_installation_id')->nullable()->constrained('app_installations')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('target_url');
            $table->string('signing_secret_encrypted');
            $table->string('status')->default('active');

            $table->index('store_id', 'idx_webhook_subscriptions_store_id');
            $table->index(['store_id', 'event_type'], 'idx_webhook_subscriptions_store_event');
            $table->index('app_installation_id', 'idx_webhook_subscriptions_installation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
