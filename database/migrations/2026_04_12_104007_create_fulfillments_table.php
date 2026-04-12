<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fulfillments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('tracking_company')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_fulfillments_order_id');
            $table->index('status', 'idx_fulfillments_status');
            $table->index(['tracking_company', 'tracking_number'], 'idx_fulfillments_tracking');
        });

        DB::statement("CREATE TRIGGER fulfillments_status_check BEFORE INSERT ON fulfillments FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('pending','shipped','delivered') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER fulfillments_status_check_update BEFORE UPDATE ON fulfillments FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('pending','shipped','delivered') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS fulfillments_status_check');
        DB::statement('DROP TRIGGER IF EXISTS fulfillments_status_check_update');
        Schema::dropIfExists('fulfillments');
    }
};
