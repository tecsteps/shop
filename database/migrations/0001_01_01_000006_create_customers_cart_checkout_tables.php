<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('name')->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['store_id', 'email']);
            $table->index('store_id');
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->json('address_json')->default('{}');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('customer_id');
            $table->index(['customer_id', 'is_default']);
        });

        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->nullable();

            $table->primary(['store_id', 'email']);
        });

        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('countries_json')->default('[]');
            $table->json('regions_json')->default('[]');
            $table->timestamps();

            $table->index('store_id');
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('flat');
            $table->json('config_json')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('zone_id');
            $table->index(['zone_id', 'is_active']);
        });

        Schema::create('tax_settings', function (Blueprint $table) {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('mode')->default('manual');
            $table->string('provider')->default('none');
            $table->boolean('prices_include_tax')->default(false);
            $table->json('config_json')->default('{}');
            $table->timestamps();
        });

        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('code');
            $table->string('code')->nullable();
            $table->string('value_type');
            $table->integer('value_amount')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->json('rules_json')->default('{}');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['store_id', 'code']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'type']);
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('cart_version')->default(1);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('store_id');
            $table->index('customer_id');
            $table->index(['store_id', 'status']);
        });

        Schema::create('cart_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->integer('unit_price_amount')->default(0);
            $table->integer('line_subtotal_amount')->default(0);
            $table->integer('line_discount_amount')->default(0);
            $table->integer('line_total_amount')->default(0);
            $table->timestamps();

            $table->unique(['cart_id', 'variant_id']);
            $table->index('cart_id');
        });

        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('started');
            $table->string('payment_method')->nullable();
            $table->string('email')->nullable();
            $table->json('shipping_address_json')->nullable();
            $table->json('billing_address_json')->nullable();
            $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_rates')->nullOnDelete();
            $table->string('discount_code')->nullable();
            $table->json('tax_provider_snapshot_json')->nullable();
            $table->json('totals_json')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('store_id');
            $table->index('cart_id');
            $table->index('customer_id');
            $table->index(['store_id', 'status']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkouts');
        Schema::dropIfExists('cart_lines');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('discounts');
        Schema::dropIfExists('tax_settings');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
