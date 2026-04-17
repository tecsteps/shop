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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->integer('amount')->default(0);
            $table->text('reason')->nullable();
            $table->text('status')->default('pending');
            $table->text('provider_refund_id')->nullable();
            $table->text('created_at')->nullable();

            $table->index('order_id', 'idx_refunds_order_id');
            $table->index('payment_id', 'idx_refunds_payment_id');
            $table->index('status', 'idx_refunds_status');
        });

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_refund_status_insert
            BEFORE INSERT ON refunds
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('pending', 'processed', 'failed')
                    THEN RAISE(ABORT, 'Invalid refund status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_refund_status_update
            BEFORE UPDATE ON refunds
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('pending', 'processed', 'failed')
                    THEN RAISE(ABORT, 'Invalid refund status')
                END;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_refund_status_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_refund_status_update');
        Schema::dropIfExists('refunds');
    }
};
