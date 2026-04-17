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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('billing_email');
            $table->timestamps();

            $table->index('billing_email', 'idx_organizations_billing_email');
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('handle');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->string('default_currency', 3)->default('USD');
            $table->string('default_locale', 10)->default('en');
            $table->string('timezone')->default('UTC');
            $table->timestamps();

            $table->unique('handle', 'idx_stores_handle');
            $table->index('organization_id', 'idx_stores_organization_id');
            $table->index('status', 'idx_stores_status');
        });

        Schema::create('store_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('hostname');
            $table->enum('type', ['storefront', 'admin', 'api'])->default('storefront');
            $table->boolean('is_primary')->default(false);
            $table->enum('tls_mode', ['managed', 'bring_your_own'])->default('managed');
            $table->timestamp('created_at')->nullable();

            $table->unique('hostname', 'idx_store_domains_hostname');
            $table->index('store_id', 'idx_store_domains_store_id');
            $table->index(['store_id', 'is_primary'], 'idx_store_domains_store_primary');
        });

        Schema::create('store_users', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'staff', 'support'])->default('staff');
            $table->timestamp('created_at')->nullable();

            $table->primary(['store_id', 'user_id']);
            $table->index('user_id', 'idx_store_users_user_id');
            $table->index(['store_id', 'role'], 'idx_store_users_role');
        });

        Schema::create('store_settings', function (Blueprint $table) {
            $table->foreignId('store_id')->primary()->constrained('stores')->cascadeOnDelete();
            $table->text('settings_json')->default('{}');
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('store_users');
        Schema::dropIfExists('store_domains');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('organizations');
    }
};
