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
        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_installation_id')->nullable()->constrained()->nullOnDelete();
            $table->text('target_url');
            $table->text('event_types_json');
            $table->text('signing_secret_encrypted');
            $table->boolean('is_active')->default(true);
            $table->integer('consecutive_failures')->default(0);
            $table->timestamp('paused_at')->nullable();
            $table->timestamps();

            $table->index('store_id', 'idx_webhook_subscriptions_store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
