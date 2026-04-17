<?php

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
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->foreignId('payment_id')
                ->constrained('payments')
                ->cascadeOnDelete();
            $table->integer('amount')->default(0);
            $table->string('reason')->nullable();
            $table->string('status')->default('pending');
            $table->string('provider_refund_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_refunds_order_id');
            $table->index('payment_id', 'idx_refunds_payment_id');
            $table->index('status', 'idx_refunds_status');
        });

        DB::statement("CREATE TRIGGER refunds_status_check BEFORE INSERT ON refunds FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('pending','processed','failed') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER refunds_status_check_update BEFORE UPDATE ON refunds FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('pending','processed','failed') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS refunds_status_check');
        DB::statement('DROP TRIGGER IF EXISTS refunds_status_check_update');
        Schema::dropIfExists('refunds');
    }
};
