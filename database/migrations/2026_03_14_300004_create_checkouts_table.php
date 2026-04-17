<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('status')->default('started');
            $table->string('payment_method')->nullable();
            $table->string('email')->nullable();
            $table->text('shipping_address_json')->nullable();
            $table->text('billing_address_json')->nullable();
            $table->integer('shipping_method_id')->nullable();
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

        DB::statement("CREATE TRIGGER check_checkouts_enums_insert
            BEFORE INSERT ON checkouts
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('started', 'addressed', 'shipping_selected', 'payment_selected', 'completed', 'expired')
                    THEN RAISE(ABORT, 'Invalid checkout status')
                END;
                SELECT CASE
                    WHEN NEW.payment_method IS NOT NULL AND NEW.payment_method NOT IN ('credit_card', 'paypal', 'bank_transfer')
                    THEN RAISE(ABORT, 'Invalid payment method')
                END;
            END;");

        DB::statement("CREATE TRIGGER check_checkouts_enums_update
            BEFORE UPDATE ON checkouts
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('started', 'addressed', 'shipping_selected', 'payment_selected', 'completed', 'expired')
                    THEN RAISE(ABORT, 'Invalid checkout status')
                END;
                SELECT CASE
                    WHEN NEW.payment_method IS NOT NULL AND NEW.payment_method NOT IN ('credit_card', 'paypal', 'bank_transfer')
                    THEN RAISE(ABORT, 'Invalid payment method')
                END;
            END;");
    }

    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};
