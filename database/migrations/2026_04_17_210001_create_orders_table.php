<?php

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
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
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('order_number');
            $table->string('payment_method');
            $table->string('status')->default(OrderStatus::Pending->value);
            $table->string('financial_status')->default(FinancialStatus::Pending->value);
            $table->string('fulfillment_status')->default(FulfillmentStatus::Unfulfilled->value);
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

        $methods = collect(PaymentMethod::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        $statuses = collect(OrderStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        $financial = collect(FinancialStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        $fulfillment = collect(FulfillmentStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');

        foreach (['insert', 'update'] as $action) {
            $when = strtoupper($action);
            DB::statement("CREATE TRIGGER orders_method_check_{$action} BEFORE {$when} ON orders FOR EACH ROW WHEN NEW.payment_method NOT IN ({$methods}) BEGIN SELECT RAISE(ABORT, 'invalid payment_method'); END");
            DB::statement("CREATE TRIGGER orders_status_check_{$action} BEFORE {$when} ON orders FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
            DB::statement("CREATE TRIGGER orders_financial_check_{$action} BEFORE {$when} ON orders FOR EACH ROW WHEN NEW.financial_status NOT IN ({$financial}) BEGIN SELECT RAISE(ABORT, 'invalid financial_status'); END");
            DB::statement("CREATE TRIGGER orders_fulfillment_check_{$action} BEFORE {$when} ON orders FOR EACH ROW WHEN NEW.fulfillment_status NOT IN ({$fulfillment}) BEGIN SELECT RAISE(ABORT, 'invalid fulfillment_status'); END");
        }
    }

    public function down(): void
    {
        foreach (['insert', 'update'] as $action) {
            foreach (['method', 'status', 'financial', 'fulfillment'] as $prefix) {
                DB::statement("DROP TRIGGER IF EXISTS orders_{$prefix}_check_{$action}");
            }
        }
        Schema::dropIfExists('orders');
    }
};
