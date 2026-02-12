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
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamp('created_at')->nullable();

            $table->index('status', 'idx_apps_status');
        });

        Schema::create('app_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->json('scopes_json')->default('[]');
            $table->string('status')->default('active');
            $table->timestamp('installed_at')->nullable();

            $table->unique(['store_id', 'app_id'], 'idx_app_installations_store_app');
            $table->index('store_id', 'idx_app_installations_store_id');
            $table->index('app_id', 'idx_app_installations_app_id');
        });

        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->string('client_id');
            $table->text('client_secret_encrypted');
            $table->json('redirect_uris_json')->default('[]');

            $table->unique('client_id', 'idx_oauth_clients_client_id');
            $table->index('app_id', 'idx_oauth_clients_app_id');
        });

        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_id')->constrained('app_installations')->cascadeOnDelete();
            $table->string('access_token_hash');
            $table->string('refresh_token_hash')->nullable();
            $table->timestamp('expires_at');

            $table->index('installation_id', 'idx_oauth_tokens_installation_id');
            $table->unique('access_token_hash', 'idx_oauth_tokens_access_hash');
            $table->index('expires_at', 'idx_oauth_tokens_expires_at');
        });

        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_installation_id')->nullable()->constrained('app_installations')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('target_url');
            $table->text('signing_secret_encrypted');
            $table->string('status')->default('active');

            $table->index('store_id', 'idx_webhook_subscriptions_store_id');
            $table->index(['store_id', 'event_type'], 'idx_webhook_subscriptions_store_event');
            $table->index('app_installation_id', 'idx_webhook_subscriptions_installation');
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('webhook_subscriptions')->cascadeOnDelete();
            $table->string('event_id');
            $table->integer('attempt_count')->default(1);
            $table->string('status')->default('pending');
            $table->timestamp('last_attempt_at')->nullable();
            $table->integer('response_code')->nullable();
            $table->text('response_body_snippet')->nullable();

            $table->index('subscription_id', 'idx_webhook_deliveries_subscription_id');
            $table->index('event_id', 'idx_webhook_deliveries_event_id');
            $table->index('status', 'idx_webhook_deliveries_status');
            $table->index('last_attempt_at', 'idx_webhook_deliveries_last_attempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_subscriptions');
        Schema::dropIfExists('oauth_tokens');
        Schema::dropIfExists('oauth_clients');
        Schema::dropIfExists('app_installations');
        Schema::dropIfExists('apps');
    }
};
