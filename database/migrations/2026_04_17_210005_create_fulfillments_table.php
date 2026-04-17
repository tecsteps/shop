<?php

use App\Enums\FulfillmentShipmentStatus;
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
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('status')->default(FulfillmentShipmentStatus::Pending->value);
            $table->string('tracking_company')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_fulfillments_order_id');
            $table->index('status', 'idx_fulfillments_status');
            $table->index(['tracking_company', 'tracking_number'], 'idx_fulfillments_tracking');
        });

        $statuses = collect(FulfillmentShipmentStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');

        foreach (['insert', 'update'] as $action) {
            $when = strtoupper($action);
            DB::statement("CREATE TRIGGER fulfillments_status_check_{$action} BEFORE {$when} ON fulfillments FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        }
    }

    public function down(): void
    {
        foreach (['insert', 'update'] as $action) {
            DB::statement("DROP TRIGGER IF EXISTS fulfillments_status_check_{$action}");
        }
        Schema::dropIfExists('fulfillments');
    }
};
