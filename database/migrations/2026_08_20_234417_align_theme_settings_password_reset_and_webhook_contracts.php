<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->migrateThemeSettings();
        $this->migrateCustomerPasswordResetTokens();
        $this->migrateWebhookSubscriptions();
    }

    public function down(): void
    {
        $this->restoreWebhookSubscriptions();
        $this->restoreCustomerPasswordResetTokens();
        $this->restoreThemeSettings();
    }

    private function migrateThemeSettings(): void
    {
        if (! Schema::hasTable('theme_settings') || Schema::hasColumn('theme_settings', 'settings_json')) {
            return;
        }

        Schema::create('theme_settings_contract', function (Blueprint $table): void {
            $table->unsignedBigInteger('theme_id')->primary();
            $table->text('settings_json')->default('{}');
            $table->timestamp('updated_at')->nullable();
            $table->foreign('theme_id')->references('id')->on('themes')->cascadeOnDelete();
        });

        $settingsByTheme = [];
        $updatedAtByTheme = [];

        DB::table('theme_settings')->orderBy('theme_id')->orderBy('id')->get()->each(function (object $row) use (&$settingsByTheme, &$updatedAtByTheme): void {
            $themeId = (int) $row->theme_id;
            $settingsByTheme[$themeId][$row->key] = $this->decodeJson($row->value);

            if ($row->updated_at !== null && (! isset($updatedAtByTheme[$themeId]) || $row->updated_at > $updatedAtByTheme[$themeId])) {
                $updatedAtByTheme[$themeId] = $row->updated_at;
            }
        });

        foreach ($settingsByTheme as $themeId => $settings) {
            DB::table('theme_settings_contract')->insert([
                'theme_id' => $themeId,
                'settings_json' => $this->encodeJsonObject($settings),
                'updated_at' => $updatedAtByTheme[$themeId] ?? null,
            ]);
        }

        $this->replaceTable('theme_settings', 'theme_settings_contract');
    }

    private function restoreThemeSettings(): void
    {
        if (! Schema::hasTable('theme_settings') || ! Schema::hasColumn('theme_settings', 'settings_json')) {
            return;
        }

        Schema::create('theme_settings_legacy', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['theme_id', 'key']);
        });

        DB::table('theme_settings')->orderBy('theme_id')->get()->each(function (object $row): void {
            $settings = $this->decodeJson($row->settings_json);

            if (! is_array($settings)) {
                return;
            }

            foreach ($settings as $key => $value) {
                DB::table('theme_settings_legacy')->insert([
                    'theme_id' => $row->theme_id,
                    'key' => (string) $key,
                    'value' => $this->encodeJson($value),
                    'created_at' => null,
                    'updated_at' => $row->updated_at,
                ]);
            }
        });

        $this->replaceTable('theme_settings', 'theme_settings_legacy');
    }

    private function migrateCustomerPasswordResetTokens(): void
    {
        if (! Schema::hasTable('customer_password_reset_tokens')) {
            return;
        }

        Schema::create('customer_password_reset_tokens_contract', function (Blueprint $table): void {
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->primary(['store_id', 'email']);
            $table->index('email');
        });

        DB::table('customer_password_reset_tokens')
            ->whereNotNull('store_id')
            ->orderBy('store_id')
            ->orderBy('email')
            ->get()
            ->each(function (object $row): void {
                DB::table('customer_password_reset_tokens_contract')->insert([
                    'store_id' => $row->store_id,
                    'email' => $row->email,
                    'token' => $row->token,
                    'created_at' => $row->created_at,
                ]);
            });

        $this->replaceTable('customer_password_reset_tokens', 'customer_password_reset_tokens_contract');
    }

    private function restoreCustomerPasswordResetTokens(): void
    {
        if (! Schema::hasTable('customer_password_reset_tokens')) {
            return;
        }

        Schema::create('customer_password_reset_tokens_legacy', function (Blueprint $table): void {
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->primary(['store_id', 'email']);
            $table->index('email');
        });

        DB::table('customer_password_reset_tokens')->get()->each(function (object $row): void {
            DB::table('customer_password_reset_tokens_legacy')->insert([
                'store_id' => $row->store_id,
                'email' => $row->email,
                'token' => $row->token,
                'created_at' => $row->created_at,
            ]);
        });

        $this->replaceTable('customer_password_reset_tokens', 'customer_password_reset_tokens_legacy');
    }

    private function migrateWebhookSubscriptions(): void
    {
        if (! Schema::hasTable('webhook_subscriptions') || Schema::hasColumn('webhook_subscriptions', 'secret_encrypted') === false) {
            return;
        }

        Schema::create('webhook_subscriptions_contract', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->string('event_type');
            $table->text('target_url');
            $table->unsignedBigInteger('app_installation_id')->nullable()->index();
            $table->text('signing_secret_encrypted');
            $table->string('status')->default('active')->index();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamps();
            $table->index(['store_id', 'event']);
            $table->index(['store_id', 'event_type']);
        });

        DB::table('webhook_subscriptions')->orderBy('id')->get()->each(function (object $row): void {
            DB::table('webhook_subscriptions_contract')->insert([
                'id' => $row->id,
                'store_id' => $row->store_id,
                'event' => $row->event,
                'event_type' => $row->event_type ?? $row->event,
                'target_url' => $row->target_url,
                'app_installation_id' => $row->app_installation_id ?? null,
                'signing_secret_encrypted' => $row->signing_secret_encrypted ?? $row->secret_encrypted,
                'status' => $row->status,
                'consecutive_failures' => $row->consecutive_failures,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });

        $this->replaceTable('webhook_subscriptions', 'webhook_subscriptions_contract');
    }

    private function restoreWebhookSubscriptions(): void
    {
        if (! Schema::hasTable('webhook_subscriptions') || ! Schema::hasColumn('webhook_subscriptions', 'signing_secret_encrypted')) {
            return;
        }

        Schema::create('webhook_subscriptions_legacy', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->string('event_type')->nullable();
            $table->text('target_url');
            $table->unsignedBigInteger('app_installation_id')->nullable()->index();
            $table->text('secret_encrypted');
            $table->string('status')->default('active');
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamps();
            $table->index(['store_id', 'event']);
        });

        DB::table('webhook_subscriptions')->orderBy('id')->get()->each(function (object $row): void {
            DB::table('webhook_subscriptions_legacy')->insert([
                'id' => $row->id,
                'store_id' => $row->store_id,
                'event' => $row->event,
                'event_type' => $row->event_type,
                'target_url' => $row->target_url,
                'app_installation_id' => $row->app_installation_id,
                'secret_encrypted' => $row->signing_secret_encrypted,
                'status' => $row->status,
                'consecutive_failures' => $row->consecutive_failures,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });

        $this->replaceTable('webhook_subscriptions', 'webhook_subscriptions_legacy');
    }

    private function replaceTable(string $table, string $replacement): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            Schema::drop($table);
            Schema::rename($replacement, $table);
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function decodeJson(mixed $value): mixed
    {
        if ($value === null || ! is_string($value)) {
            return $value;
        }

        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $value;
        }
    }

    private function encodeJson(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /** @param array<string, mixed> $settings */
    private function encodeJsonObject(array $settings): string
    {
        return $this->encodeJson($settings === [] ? (object) [] : $settings);
    }
};
