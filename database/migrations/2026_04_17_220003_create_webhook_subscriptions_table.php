<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('app_installation_id')->nullable()->constrained('app_installations')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('target_url');
            $table->text('signing_secret_encrypted');
            $table->string('status')->default('active');
            $table->integer('consecutive_failures')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index('store_id', 'idx_webhook_subscriptions_store_id');
            $table->index(['store_id', 'event_type'], 'idx_webhook_subscriptions_store_event');
            $table->index('app_installation_id', 'idx_webhook_subscriptions_installation');
        });

        $statuses = "'active','paused','disabled'";
        DB::statement("CREATE TRIGGER webhook_subs_status_check_insert BEFORE INSERT ON webhook_subscriptions FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER webhook_subs_status_check_update BEFORE UPDATE ON webhook_subscriptions FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS webhook_subs_status_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS webhook_subs_status_check_update');
        Schema::dropIfExists('webhook_subscriptions');
    }
};
