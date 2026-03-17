<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_installation_id')->nullable()->constrained('app_installations')->cascadeOnDelete();
            $table->text('event_type');
            $table->text('target_url');
            $table->text('signing_secret_encrypted');
            $table->text('status')->default('active');

            $table->index('store_id', 'idx_webhook_subscriptions_store_id');
            $table->index(['store_id', 'event_type'], 'idx_webhook_subscriptions_store_event');
            $table->index('app_installation_id', 'idx_webhook_subscriptions_installation');
        });

        DB::statement("CREATE TRIGGER check_webhook_subscriptions_status INSERT ON webhook_subscriptions
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('active', 'paused', 'disabled')
                    THEN RAISE(ABORT, 'Invalid webhook subscription status')
                END;
            END");

        DB::statement("CREATE TRIGGER check_webhook_subscriptions_status_update UPDATE OF status ON webhook_subscriptions
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('active', 'paused', 'disabled')
                    THEN RAISE(ABORT, 'Invalid webhook subscription status')
                END;
            END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_webhook_subscriptions_status');
        DB::statement('DROP TRIGGER IF EXISTS check_webhook_subscriptions_status_update');
        Schema::dropIfExists('webhook_subscriptions');
    }
};
