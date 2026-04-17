<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->constrained('webhook_subscriptions')->cascadeOnDelete();
            $table->string('event_id');
            $table->integer('attempt_count')->default(1);
            $table->string('status')->default('pending');
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->integer('response_code')->nullable();
            $table->text('response_body_snippet')->nullable();

            $table->index('subscription_id', 'idx_webhook_deliveries_subscription_id');
            $table->index('event_id', 'idx_webhook_deliveries_event_id');
            $table->index('status', 'idx_webhook_deliveries_status');
            $table->index('last_attempt_at', 'idx_webhook_deliveries_last_attempt');
        });

        $statuses = "'pending','success','failed'";
        DB::statement("CREATE TRIGGER webhook_deliveries_status_check_insert BEFORE INSERT ON webhook_deliveries FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER webhook_deliveries_status_check_update BEFORE UPDATE ON webhook_deliveries FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS webhook_deliveries_status_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS webhook_deliveries_status_check_update');
        Schema::dropIfExists('webhook_deliveries');
    }
};
