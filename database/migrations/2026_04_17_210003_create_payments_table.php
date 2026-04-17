<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('provider')->default('mock');
            $table->string('method');
            $table->string('provider_payment_id')->nullable();
            $table->string('status')->default(PaymentStatus::Pending->value);
            $table->integer('amount')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('raw_json_encrypted')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_payments_order_id');
            $table->index(['provider', 'provider_payment_id'], 'idx_payments_provider_id');
            $table->index('method', 'idx_payments_method');
            $table->index('status', 'idx_payments_status');
        });

        $methods = collect(PaymentMethod::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        $statuses = collect(PaymentStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');

        foreach (['insert', 'update'] as $action) {
            $when = strtoupper($action);
            DB::statement("CREATE TRIGGER payments_method_check_{$action} BEFORE {$when} ON payments FOR EACH ROW WHEN NEW.method NOT IN ({$methods}) BEGIN SELECT RAISE(ABORT, 'invalid method'); END");
            DB::statement("CREATE TRIGGER payments_status_check_{$action} BEFORE {$when} ON payments FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
            DB::statement("CREATE TRIGGER payments_provider_check_{$action} BEFORE {$when} ON payments FOR EACH ROW WHEN NEW.provider NOT IN ('mock') BEGIN SELECT RAISE(ABORT, 'invalid provider'); END");
        }
    }

    public function down(): void
    {
        foreach (['insert', 'update'] as $action) {
            foreach (['method', 'status', 'provider'] as $prefix) {
                DB::statement("DROP TRIGGER IF EXISTS payments_{$prefix}_check_{$action}");
            }
        }
        Schema::dropIfExists('payments');
    }
};
