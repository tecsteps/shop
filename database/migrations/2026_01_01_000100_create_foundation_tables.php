<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('billing_email');
            $table->timestamps();
            $table->index('billing_email', 'idx_organizations_billing_email');
        });

        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', ['active', 'disabled'])->default('active');
            $table->timestamp('created_at')->nullable();
            $table->index('status', 'idx_apps_status');
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('handle');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->string('default_currency')->default('USD');
            $table->string('default_locale')->default('en');
            $table->string('timezone')->default('UTC');
            $table->timestamps();
            $table->unique('handle', 'idx_stores_handle');
            $table->index('organization_id', 'idx_stores_organization_id');
            $table->index('status', 'idx_stores_status');
        });

        Schema::create('store_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
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
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['owner', 'admin', 'staff', 'support'])->default('staff');
            $table->timestamp('created_at')->nullable();
            $table->primary(['store_id', 'user_id']);
            $table->index('user_id', 'idx_store_users_user_id');
            $table->index(['store_id', 'role'], 'idx_store_users_role');
        });

        Schema::create('store_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->primary();
            $table->text('settings_json')->default('{}');
            $table->timestamp('updated_at')->nullable();
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
        });

        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->primary(['store_id', 'email']);
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('store_users');
        Schema::dropIfExists('store_domains');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('apps');
        Schema::dropIfExists('organizations');
    }
};
