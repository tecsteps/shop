<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->string('order_number');
            $table->string('payment_method');
            $table->string('status')->default('pending');
            $table->string('financial_status')->default('pending');
            $table->string('fulfillment_status')->default('unfulfilled');
            $table->string('currency', 3)->default('USD');
            $table->integer('subtotal_amount')->default(0);
            $table->integer('discount_amount')->default(0);
            $table->integer('shipping_amount')->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('total_amount')->default(0);
            $table->string('email')->nullable();
            $table->text('billing_address_json')->nullable();
            $table->text('shipping_address_json')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'order_number'], 'idx_orders_store_order_number');
            $table->index('store_id', 'idx_orders_store_id');
            $table->index('customer_id', 'idx_orders_customer_id');
            $table->index(['store_id', 'status'], 'idx_orders_store_status');
            $table->index(['store_id', 'financial_status'], 'idx_orders_store_financial');
            $table->index(['store_id', 'fulfillment_status'], 'idx_orders_store_fulfillment');
            $table->index(['store_id', 'placed_at'], 'idx_orders_placed_at');
        });

        DB::statement("CREATE TRIGGER orders_payment_method_check BEFORE INSERT ON orders FOR EACH ROW BEGIN SELECT CASE WHEN NEW.payment_method NOT IN ('credit_card','paypal','bank_transfer') THEN RAISE(ABORT, 'invalid payment_method') END; END");
        DB::statement("CREATE TRIGGER orders_payment_method_check_update BEFORE UPDATE ON orders FOR EACH ROW BEGIN SELECT CASE WHEN NEW.payment_method NOT IN ('credit_card','paypal','bank_transfer') THEN RAISE(ABORT, 'invalid payment_method') END; END");
        DB::statement("CREATE TRIGGER orders_status_check BEFORE INSERT ON orders FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('pending','paid','fulfilled','cancelled','refunded') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER orders_status_check_update BEFORE UPDATE ON orders FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('pending','paid','fulfilled','cancelled','refunded') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER orders_financial_status_check BEFORE INSERT ON orders FOR EACH ROW BEGIN SELECT CASE WHEN NEW.financial_status NOT IN ('pending','authorized','paid','partially_refunded','refunded','voided') THEN RAISE(ABORT, 'invalid financial_status') END; END");
        DB::statement("CREATE TRIGGER orders_financial_status_check_update BEFORE UPDATE ON orders FOR EACH ROW BEGIN SELECT CASE WHEN NEW.financial_status NOT IN ('pending','authorized','paid','partially_refunded','refunded','voided') THEN RAISE(ABORT, 'invalid financial_status') END; END");
        DB::statement("CREATE TRIGGER orders_fulfillment_status_check BEFORE INSERT ON orders FOR EACH ROW BEGIN SELECT CASE WHEN NEW.fulfillment_status NOT IN ('unfulfilled','partial','fulfilled') THEN RAISE(ABORT, 'invalid fulfillment_status') END; END");
        DB::statement("CREATE TRIGGER orders_fulfillment_status_check_update BEFORE UPDATE ON orders FOR EACH ROW BEGIN SELECT CASE WHEN NEW.fulfillment_status NOT IN ('unfulfilled','partial','fulfilled') THEN RAISE(ABORT, 'invalid fulfillment_status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS orders_payment_method_check');
        DB::statement('DROP TRIGGER IF EXISTS orders_payment_method_check_update');
        DB::statement('DROP TRIGGER IF EXISTS orders_status_check');
        DB::statement('DROP TRIGGER IF EXISTS orders_status_check_update');
        DB::statement('DROP TRIGGER IF EXISTS orders_financial_status_check');
        DB::statement('DROP TRIGGER IF EXISTS orders_financial_status_check_update');
        DB::statement('DROP TRIGGER IF EXISTS orders_fulfillment_status_check');
        DB::statement('DROP TRIGGER IF EXISTS orders_fulfillment_status_check_update');
        Schema::dropIfExists('orders');
    }
};
