<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table): void {
            if (! Schema::hasColumn('collections', 'description_html')) {
                $table->longText('description_html')->nullable();
            }

            if (! Schema::hasColumn('collections', 'type')) {
                $table->string('type')->default('manual')->index();
            }
        });

        Schema::table('themes', function (Blueprint $table): void {
            if (! Schema::hasColumn('themes', 'published_at')) {
                $table->timestamp('published_at')->nullable()->index();
            }
        });

        Schema::table('navigation_menus', function (Blueprint $table): void {
            if (! Schema::hasColumn('navigation_menus', 'title')) {
                $table->string('title')->nullable();
            }
        });

        Schema::table('search_queries', function (Blueprint $table): void {
            if (! Schema::hasColumn('search_queries', 'filters_json')) {
                $table->json('filters_json')->nullable();
            }
        });

        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'name')) {
                $table->string('name')->nullable();
            }

            if (! Schema::hasColumn('customers', 'marketing_opt_in')) {
                $table->boolean('marketing_opt_in')->default(false)->index();
            }
        });

        Schema::table('app_installations', function (Blueprint $table): void {
            if (! Schema::hasColumn('app_installations', 'scopes_json')) {
                $table->json('scopes_json')->nullable();
            }

            if (! Schema::hasColumn('app_installations', 'installed_at')) {
                $table->timestamp('installed_at')->nullable();
            }
        });

        Schema::table('oauth_clients', function (Blueprint $table): void {
            if (! Schema::hasColumn('oauth_clients', 'app_id')) {
                $table->unsignedBigInteger('app_id')->nullable()->index();
            }

            if (! Schema::hasColumn('oauth_clients', 'redirect_uris_json')) {
                $table->json('redirect_uris_json')->nullable();
            }
        });

        Schema::table('oauth_tokens', function (Blueprint $table): void {
            if (! Schema::hasColumn('oauth_tokens', 'installation_id')) {
                $table->unsignedBigInteger('installation_id')->nullable()->index();
            }

            if (! Schema::hasColumn('oauth_tokens', 'access_token_hash')) {
                $table->string('access_token_hash')->nullable()->unique();
            }

            if (! Schema::hasColumn('oauth_tokens', 'refresh_token_hash')) {
                $table->string('refresh_token_hash')->nullable();
            }
        });

        Schema::table('webhook_subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('webhook_subscriptions', 'signing_secret_encrypted')) {
                $table->text('signing_secret_encrypted')->nullable();
            }
        });

        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            if (! Schema::hasColumn('webhook_deliveries', 'last_attempt_at')) {
                $table->timestamp('last_attempt_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        foreach ([
            'description_html' => 'collections',
            'type' => 'collections',
            'published_at' => 'themes',
            'title' => 'navigation_menus',
            'filters_json' => 'search_queries',
            'name' => 'customers',
            'marketing_opt_in' => 'customers',
            'scopes_json' => 'app_installations',
            'installed_at' => 'app_installations',
            'app_id' => 'oauth_clients',
            'redirect_uris_json' => 'oauth_clients',
            'installation_id' => 'oauth_tokens',
            'access_token_hash' => 'oauth_tokens',
            'refresh_token_hash' => 'oauth_tokens',
            'signing_secret_encrypted' => 'webhook_subscriptions',
            'last_attempt_at' => 'webhook_deliveries',
        ] as $column => $tableName) {
            if (Schema::hasColumn($tableName, $column)) {
                Schema::table($tableName, function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
