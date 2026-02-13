<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('webhook_subscriptions')->cascadeOnDelete();
            $table->text('event_type');
            $table->text('payload_json');
            $table->text('status')->default('pending');
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('attempt_count')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('subscription_id', 'idx_webhook_deliveries_subscription_id');
            $table->index('status', 'idx_webhook_deliveries_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
