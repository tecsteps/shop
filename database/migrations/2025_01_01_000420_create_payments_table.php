<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->text('provider')->default('mock');
            $table->text('method');
            $table->text('provider_payment_id')->nullable();
            $table->text('status')->default('pending');
            $table->integer('amount')->default(0);
            $table->text('currency')->default('USD');
            $table->text('raw_json_encrypted')->nullable();
            $table->text('created_at')->nullable();

            $table->index('order_id', 'idx_payments_order_id');
            $table->index(['provider', 'provider_payment_id'], 'idx_payments_provider_id');
            $table->index('method', 'idx_payments_method');
            $table->index('status', 'idx_payments_status');
        });

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_payment_provider_insert
            BEFORE INSERT ON payments
            BEGIN
                SELECT CASE
                    WHEN NEW.provider NOT IN ('mock')
                    THEN RAISE(ABORT, 'Invalid payment provider')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_payment_provider_update
            BEFORE UPDATE ON payments
            BEGIN
                SELECT CASE
                    WHEN NEW.provider NOT IN ('mock')
                    THEN RAISE(ABORT, 'Invalid payment provider')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_payment_method_insert
            BEFORE INSERT ON payments
            BEGIN
                SELECT CASE
                    WHEN NEW.method NOT IN ('credit_card', 'paypal', 'bank_transfer')
                    THEN RAISE(ABORT, 'Invalid payment method')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_payment_method_update
            BEFORE UPDATE ON payments
            BEGIN
                SELECT CASE
                    WHEN NEW.method NOT IN ('credit_card', 'paypal', 'bank_transfer')
                    THEN RAISE(ABORT, 'Invalid payment method')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_payment_status_insert
            BEFORE INSERT ON payments
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('pending', 'captured', 'failed', 'refunded')
                    THEN RAISE(ABORT, 'Invalid payment status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_payment_status_update
            BEFORE UPDATE ON payments
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('pending', 'captured', 'failed', 'refunded')
                    THEN RAISE(ABORT, 'Invalid payment status')
                END;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_payment_provider_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_payment_provider_update');
        DB::statement('DROP TRIGGER IF EXISTS check_payment_method_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_payment_method_update');
        DB::statement('DROP TRIGGER IF EXISTS check_payment_status_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_payment_status_update');
        Schema::dropIfExists('payments');
    }
};
