<?php

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('status')->default(CheckoutStatus::Started->value);
            $table->string('payment_method')->nullable();
            $table->string('email')->nullable();
            $table->text('shipping_address_json')->nullable();
            $table->text('billing_address_json')->nullable();
            $table->unsignedBigInteger('shipping_method_id')->nullable();
            $table->string('discount_code')->nullable();
            $table->text('tax_provider_snapshot_json')->nullable();
            $table->text('totals_json')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('store_id', 'idx_checkouts_store_id');
            $table->index('cart_id', 'idx_checkouts_cart_id');
            $table->index('customer_id', 'idx_checkouts_customer_id');
            $table->index(['store_id', 'status'], 'idx_checkouts_status');
            $table->index('expires_at', 'idx_checkouts_expires_at');
        });

        $statuses = collect(CheckoutStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        $methods = collect(PaymentMethod::values())->map(fn (string $v): string => "'".$v."'")->implode(',');

        DB::statement("CREATE TRIGGER checkouts_status_check_insert BEFORE INSERT ON checkouts FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER checkouts_status_check_update BEFORE UPDATE ON checkouts FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER checkouts_payment_check_insert BEFORE INSERT ON checkouts FOR EACH ROW WHEN NEW.payment_method IS NOT NULL AND NEW.payment_method NOT IN ({$methods}) BEGIN SELECT RAISE(ABORT, 'invalid payment_method'); END");
        DB::statement("CREATE TRIGGER checkouts_payment_check_update BEFORE UPDATE ON checkouts FOR EACH ROW WHEN NEW.payment_method IS NOT NULL AND NEW.payment_method NOT IN ({$methods}) BEGIN SELECT RAISE(ABORT, 'invalid payment_method'); END");
    }

    public function down(): void
    {
        foreach (['checkouts_status_check_insert', 'checkouts_status_check_update', 'checkouts_payment_check_insert', 'checkouts_payment_check_update'] as $trigger) {
            DB::statement("DROP TRIGGER IF EXISTS {$trigger}");
        }
        Schema::dropIfExists('checkouts');
    }
};
