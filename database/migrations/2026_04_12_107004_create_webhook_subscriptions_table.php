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
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->foreignId('app_installation_id')
                ->nullable()
                ->constrained('app_installations')
                ->nullOnDelete();
            $table->string('event_type');
            $table->string('url');
            $table->string('secret');
            $table->string('status')->default('active');
            $table->integer('failed_count')->default(0);
            $table->timestamps();

            $table->index(['store_id', 'event_type', 'status'], 'idx_webhook_subs_store_event_status');
            $table->index('app_installation_id', 'idx_webhook_subs_installation');
        });

        DB::statement("CREATE TRIGGER webhook_subs_status_check BEFORE INSERT ON webhook_subscriptions FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('active','paused','disabled') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER webhook_subs_status_check_update BEFORE UPDATE ON webhook_subscriptions FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('active','paused','disabled') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS webhook_subs_status_check');
        DB::statement('DROP TRIGGER IF EXISTS webhook_subs_status_check_update');
        Schema::dropIfExists('webhook_subscriptions');
    }
};
