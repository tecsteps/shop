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
        Schema::create('fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->text('status')->default('pending');
            $table->text('tracking_company')->nullable();
            $table->text('tracking_number')->nullable();
            $table->text('tracking_url')->nullable();
            $table->text('shipped_at')->nullable();
            $table->text('created_at')->nullable();

            $table->index('order_id', 'idx_fulfillments_order_id');
            $table->index('status', 'idx_fulfillments_status');
            $table->index(['tracking_company', 'tracking_number'], 'idx_fulfillments_tracking');
        });

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_fulfillment_status_insert
            BEFORE INSERT ON fulfillments
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('pending', 'shipped', 'delivered')
                    THEN RAISE(ABORT, 'Invalid fulfillment status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_fulfillment_status_update
            BEFORE UPDATE ON fulfillments
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('pending', 'shipped', 'delivered')
                    THEN RAISE(ABORT, 'Invalid fulfillment status')
                END;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_fulfillment_status_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_fulfillment_status_update');
        Schema::dropIfExists('fulfillments');
    }
};
