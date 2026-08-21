<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('themes', 'settings')) {
            return;
        }

        DB::table('themes')->whereNotNull('settings')->orderBy('id')->get()->each(function (object $theme): void {
            $settings = is_string($theme->settings)
                ? json_decode($theme->settings, true)
                : $theme->settings;

            DB::table('theme_settings')->updateOrInsert(
                ['theme_id' => $theme->id],
                ['settings_json' => json_encode(is_array($settings) ? $settings : new \stdClass), 'updated_at' => now()],
            );
        });

        Schema::table('themes', function (Blueprint $table): void {
            $table->dropColumn('settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('themes', 'settings')) {
            return;
        }

        Schema::table('themes', function (Blueprint $table): void {
            $table->json('settings')->nullable();
        });

        DB::table('theme_settings')->orderBy('theme_id')->get()->each(function (object $settings): void {
            DB::table('themes')->where('id', $settings->theme_id)->update(['settings' => $settings->settings_json]);
        });
    }
};
