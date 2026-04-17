<?php

use App\Enums\RefundStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->integer('amount')->default(0);
            $table->string('reason')->nullable();
            $table->string('status')->default(RefundStatus::Pending->value);
            $table->string('provider_refund_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_refunds_order_id');
            $table->index('payment_id', 'idx_refunds_payment_id');
            $table->index('status', 'idx_refunds_status');
        });

        $statuses = collect(RefundStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');

        foreach (['insert', 'update'] as $action) {
            $when = strtoupper($action);
            DB::statement("CREATE TRIGGER refunds_status_check_{$action} BEFORE {$when} ON refunds FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        }
    }

    public function down(): void
    {
        foreach (['insert', 'update'] as $action) {
            DB::statement("DROP TRIGGER IF EXISTS refunds_status_check_{$action}");
        }
        Schema::dropIfExists('refunds');
    }
};
