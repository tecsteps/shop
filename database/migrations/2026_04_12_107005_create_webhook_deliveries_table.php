<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')
                ->constrained('webhook_subscriptions')
                ->cascadeOnDelete();
            $table->string('event_type');
            $table->text('payload_json');
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subscription_id', 'created_at'], 'idx_webhook_deliveries_sub_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
