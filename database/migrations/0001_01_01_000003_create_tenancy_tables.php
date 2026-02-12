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
            $table->string('billing_email')->index();
            $table->timestamps();
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('handle')->unique();
            $table->string('status')->default('active')->index();
            $table->string('default_currency', 3)->default('USD');
            $table->string('default_locale')->default('en');
            $table->string('timezone')->default('UTC');
            $table->timestamps();

            $table->index('organization_id');
        });

        Schema::create('store_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('hostname')->unique();
            $table->string('type')->default('storefront');
            $table->boolean('is_primary')->default(false);
            $table->string('tls_mode')->default('managed');
            $table->timestamps();

            $table->index('store_id');
            $table->index(['store_id', 'is_primary']);
        });

        Schema::create('store_users', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('staff');
            $table->timestamp('created_at')->nullable();

            $table->primary(['store_id', 'user_id']);
            $table->index('user_id');
            $table->index(['store_id', 'role']);
        });

        Schema::create('store_settings', function (Blueprint $table) {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->json('settings_json')->default('{}');
            $table->timestamps();
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
