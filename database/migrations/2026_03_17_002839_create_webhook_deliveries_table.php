<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('webhook_subscriptions')->cascadeOnDelete();
            $table->text('event_id');
            $table->integer('attempt_count')->default(1);
            $table->text('status')->default('pending');
            $table->text('last_attempt_at')->nullable();
            $table->integer('response_code')->nullable();
            $table->text('response_body_snippet')->nullable();

            $table->index('subscription_id', 'idx_webhook_deliveries_subscription_id');
            $table->index('event_id', 'idx_webhook_deliveries_event_id');
            $table->index('status', 'idx_webhook_deliveries_status');
            $table->index('last_attempt_at', 'idx_webhook_deliveries_last_attempt');
        });

        DB::statement("CREATE TRIGGER check_webhook_deliveries_status INSERT ON webhook_deliveries
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('pending', 'success', 'failed')
                    THEN RAISE(ABORT, 'Invalid webhook delivery status')
                END;
            END");

        DB::statement("CREATE TRIGGER check_webhook_deliveries_status_update UPDATE OF status ON webhook_deliveries
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('pending', 'success', 'failed')
                    THEN RAISE(ABORT, 'Invalid webhook delivery status')
                END;
            END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_webhook_deliveries_status');
        DB::statement('DROP TRIGGER IF EXISTS check_webhook_deliveries_status_update');
        Schema::dropIfExists('webhook_deliveries');
    }
};
