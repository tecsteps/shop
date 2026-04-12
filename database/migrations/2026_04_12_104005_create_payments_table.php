<?php

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
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->string('provider')->default('mock');
            $table->string('method');
            $table->string('provider_payment_id')->nullable();
            $table->string('status')->default('pending');
            $table->integer('amount')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('raw_json_encrypted')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('order_id', 'idx_payments_order_id');
            $table->index(['provider', 'provider_payment_id'], 'idx_payments_provider_id');
            $table->index('method', 'idx_payments_method');
            $table->index('status', 'idx_payments_status');
        });

        DB::statement("CREATE TRIGGER payments_provider_check BEFORE INSERT ON payments FOR EACH ROW BEGIN SELECT CASE WHEN NEW.provider NOT IN ('mock') THEN RAISE(ABORT, 'invalid provider') END; END");
        DB::statement("CREATE TRIGGER payments_provider_check_update BEFORE UPDATE ON payments FOR EACH ROW BEGIN SELECT CASE WHEN NEW.provider NOT IN ('mock') THEN RAISE(ABORT, 'invalid provider') END; END");
        DB::statement("CREATE TRIGGER payments_method_check BEFORE INSERT ON payments FOR EACH ROW BEGIN SELECT CASE WHEN NEW.method NOT IN ('credit_card','paypal','bank_transfer') THEN RAISE(ABORT, 'invalid method') END; END");
        DB::statement("CREATE TRIGGER payments_method_check_update BEFORE UPDATE ON payments FOR EACH ROW BEGIN SELECT CASE WHEN NEW.method NOT IN ('credit_card','paypal','bank_transfer') THEN RAISE(ABORT, 'invalid method') END; END");
        DB::statement("CREATE TRIGGER payments_status_check BEFORE INSERT ON payments FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('pending','captured','failed','refunded') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER payments_status_check_update BEFORE UPDATE ON payments FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('pending','captured','failed','refunded') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS payments_provider_check');
        DB::statement('DROP TRIGGER IF EXISTS payments_provider_check_update');
        DB::statement('DROP TRIGGER IF EXISTS payments_method_check');
        DB::statement('DROP TRIGGER IF EXISTS payments_method_check_update');
        DB::statement('DROP TRIGGER IF EXISTS payments_status_check');
        DB::statement('DROP TRIGGER IF EXISTS payments_status_check_update');
        Schema::dropIfExists('payments');
    }
};
