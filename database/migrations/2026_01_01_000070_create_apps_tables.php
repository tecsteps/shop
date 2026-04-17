<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique();
            $table->string('description')->nullable();
            $table->text('scopes_json')->default('[]');
            $table->timestamps();
        });

        Schema::create('app_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->text('scopes_granted_json')->default('[]');
            $table->timestamp('installed_at')->nullable();

            $table->unique(['store_id', 'app_id'], 'idx_app_installations_store_app');
        });

        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_installation_id')->nullable()->constrained('app_installations')->nullOnDelete();
            $table->string('topic');
            $table->string('target_url');
            $table->string('secret');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['store_id', 'topic'], 'idx_webhook_subs_store_topic');
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_subscription_id')->constrained('webhook_subscriptions')->cascadeOnDelete();
            $table->string('topic');
            $table->text('payload_json');
            $table->integer('response_status')->nullable();
            $table->integer('attempts')->default(0);
            $table->string('status')->default('pending');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('webhook_subscription_id', 'idx_webhook_deliveries_sub_id');
            $table->index('status', 'idx_webhook_deliveries_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_subscriptions');
        Schema::dropIfExists('app_installations');
        Schema::dropIfExists('apps');
    }
};
