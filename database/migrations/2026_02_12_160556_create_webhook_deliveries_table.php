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
            $table->foreignId('webhook_subscription_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->text('payload_json');
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('attempts')->default(0);
            $table->datetime('next_retry_at')->nullable();
            $table->datetime('delivered_at')->nullable();
            $table->datetime('failed_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('webhook_subscription_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
