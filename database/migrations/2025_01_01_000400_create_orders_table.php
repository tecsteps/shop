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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->text('order_number');
            $table->text('payment_method');
            $table->text('status')->default('pending');
            $table->text('financial_status')->default('pending');
            $table->text('fulfillment_status')->default('unfulfilled');
            $table->text('currency')->default('USD');
            $table->integer('subtotal_amount')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('shipping_amount')->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('total_amount')->default(0);
            $table->text('email')->nullable();
            $table->text('billing_address_json')->nullable();
            $table->text('shipping_address_json')->nullable();
            $table->text('placed_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'order_number'], 'idx_orders_store_order_number');
            $table->index('store_id', 'idx_orders_store_id');
            $table->index('customer_id', 'idx_orders_customer_id');
            $table->index(['store_id', 'status'], 'idx_orders_store_status');
            $table->index(['store_id', 'financial_status'], 'idx_orders_store_financial');
            $table->index(['store_id', 'fulfillment_status'], 'idx_orders_store_fulfillment');
            $table->index(['store_id', 'placed_at'], 'idx_orders_placed_at');
        });

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_order_status_insert
            BEFORE INSERT ON orders
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('pending', 'paid', 'fulfilled', 'cancelled', 'refunded')
                    THEN RAISE(ABORT, 'Invalid order status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_order_status_update
            BEFORE UPDATE ON orders
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('pending', 'paid', 'fulfilled', 'cancelled', 'refunded')
                    THEN RAISE(ABORT, 'Invalid order status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_order_financial_status_insert
            BEFORE INSERT ON orders
            BEGIN
                SELECT CASE
                    WHEN NEW.financial_status NOT IN ('pending', 'authorized', 'paid', 'partially_refunded', 'refunded', 'voided')
                    THEN RAISE(ABORT, 'Invalid financial status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_order_financial_status_update
            BEFORE UPDATE ON orders
            BEGIN
                SELECT CASE
                    WHEN NEW.financial_status NOT IN ('pending', 'authorized', 'paid', 'partially_refunded', 'refunded', 'voided')
                    THEN RAISE(ABORT, 'Invalid financial status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_order_fulfillment_status_insert
            BEFORE INSERT ON orders
            BEGIN
                SELECT CASE
                    WHEN NEW.fulfillment_status NOT IN ('unfulfilled', 'partial', 'fulfilled')
                    THEN RAISE(ABORT, 'Invalid fulfillment status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_order_fulfillment_status_update
            BEFORE UPDATE ON orders
            BEGIN
                SELECT CASE
                    WHEN NEW.fulfillment_status NOT IN ('unfulfilled', 'partial', 'fulfilled')
                    THEN RAISE(ABORT, 'Invalid fulfillment status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_order_payment_method_insert
            BEFORE INSERT ON orders
            BEGIN
                SELECT CASE
                    WHEN NEW.payment_method NOT IN ('credit_card', 'paypal', 'bank_transfer')
                    THEN RAISE(ABORT, 'Invalid payment method')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_order_payment_method_update
            BEFORE UPDATE ON orders
            BEGIN
                SELECT CASE
                    WHEN NEW.payment_method NOT IN ('credit_card', 'paypal', 'bank_transfer')
                    THEN RAISE(ABORT, 'Invalid payment method')
                END;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_order_status_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_order_status_update');
        DB::statement('DROP TRIGGER IF EXISTS check_order_financial_status_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_order_financial_status_update');
        DB::statement('DROP TRIGGER IF EXISTS check_order_fulfillment_status_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_order_fulfillment_status_update');
        DB::statement('DROP TRIGGER IF EXISTS check_order_payment_method_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_order_payment_method_update');
        Schema::dropIfExists('orders');
    }
};
