<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'password_hash')) {
                $table->text('password_hash')->nullable();
            }
        });
        Schema::table('customer_password_reset_tokens', function (Blueprint $table): void {
            if (! Schema::hasColumn('customer_password_reset_tokens', 'store_id')) {
                $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            }
        });
        Schema::table('product_media', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_media', 'width')) {
                $table->unsignedInteger('width')->nullable();
            }
            if (! Schema::hasColumn('product_media', 'height')) {
                $table->unsignedInteger('height')->nullable();
            }
        });
        Schema::table('theme_files', function (Blueprint $table): void {
            if (! Schema::hasColumn('theme_files', 'storage_key')) {
                $table->string('storage_key')->nullable();
            }
            if (! Schema::hasColumn('theme_files', 'sha256')) {
                $table->string('sha256')->nullable();
            }
            if (! Schema::hasColumn('theme_files', 'byte_size')) {
                $table->unsignedBigInteger('byte_size')->default(0);
            }
        });
        Schema::table('pages', function (Blueprint $table): void {
            if (! Schema::hasColumn('pages', 'body_html')) {
                $table->longText('body_html')->nullable();
            }
        });
        Schema::table('navigation_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('navigation_items', 'menu_id')) {
                $table->unsignedBigInteger('menu_id')->nullable()->index();
            }
        });
        Schema::table('search_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('search_settings', 'synonyms_json')) {
                $table->json('synonyms_json')->nullable();
            }
            if (! Schema::hasColumn('search_settings', 'stop_words_json')) {
                $table->json('stop_words_json')->nullable();
            }
        });
        Schema::table('order_lines', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_lines', 'total_amount')) {
                $table->unsignedInteger('total_amount')->nullable();
            }
        });
        Schema::table('payments', function (Blueprint $table): void {
            if (! Schema::hasColumn('payments', 'currency')) {
                $table->string('currency', 3)->default('USD');
            }
        });
        Schema::table('analytics_events', function (Blueprint $table): void {
            if (! Schema::hasColumn('analytics_events', 'properties_json')) {
                $table->json('properties_json')->nullable();
            }
            if (! Schema::hasColumn('analytics_events', 'occurred_at')) {
                $table->timestamp('occurred_at')->nullable();
            }
        });
        Schema::table('analytics_daily', function (Blueprint $table): void {
            if (! Schema::hasColumn('analytics_daily', 'checkout_completed_count')) {
                $table->unsignedInteger('checkout_completed_count')->default(0);
            }
        });
        Schema::table('webhook_subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('webhook_subscriptions', 'event_type')) {
                $table->string('event_type')->nullable()->index();
            }
            if (! Schema::hasColumn('webhook_subscriptions', 'app_installation_id')) {
                $table->unsignedBigInteger('app_installation_id')->nullable()->index();
            }
        });
        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            if (! Schema::hasColumn('webhook_deliveries', 'subscription_id')) {
                $table->unsignedBigInteger('subscription_id')->nullable()->index();
            }
            if (! Schema::hasColumn('webhook_deliveries', 'event_id')) {
                $table->string('event_id')->nullable()->index();
            }
            if (! Schema::hasColumn('webhook_deliveries', 'attempt_count')) {
                $table->unsignedInteger('attempt_count')->default(0);
            }
            if (! Schema::hasColumn('webhook_deliveries', 'last_attempt_at')) {
                $table->timestamp('last_attempt_at')->nullable();
            }
            if (! Schema::hasColumn('webhook_deliveries', 'response_code')) {
                $table->unsignedSmallInteger('response_code')->nullable();
            }
            if (! Schema::hasColumn('webhook_deliveries', 'response_body_snippet')) {
                $table->text('response_body_snippet')->nullable();
            }
        });
    }

    public function down(): void
    {
        foreach (['password_hash' => 'users', 'store_id' => 'customer_password_reset_tokens', 'width' => 'product_media', 'height' => 'product_media', 'storage_key' => 'theme_files', 'sha256' => 'theme_files', 'byte_size' => 'theme_files', 'body_html' => 'pages', 'menu_id' => 'navigation_items', 'synonyms_json' => 'search_settings', 'stop_words_json' => 'search_settings', 'total_amount' => 'order_lines', 'currency' => 'payments', 'properties_json' => 'analytics_events', 'occurred_at' => 'analytics_events', 'checkout_completed_count' => 'analytics_daily', 'event_type' => 'webhook_subscriptions', 'app_installation_id' => 'webhook_subscriptions', 'subscription_id' => 'webhook_deliveries', 'event_id' => 'webhook_deliveries', 'attempt_count' => 'webhook_deliveries', 'last_attempt_at' => 'webhook_deliveries', 'response_code' => 'webhook_deliveries', 'response_body_snippet' => 'webhook_deliveries'] as $column => $tableName) {
            if (Schema::hasColumn($tableName, $column)) {
                Schema::table($tableName, function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
