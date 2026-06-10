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
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('webhook_subscriptions')->cascadeOnDelete();
            $table->string('event_id');
            $table->unsignedInteger('attempt_count')->default(1);
            $table->string('status')->default('pending');
            $table->timestamp('last_attempt_at')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body_snippet')->nullable();

            $table->index('event_id');
            $table->index('status');
            $table->index('last_attempt_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
