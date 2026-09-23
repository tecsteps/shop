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
        Schema::table('tax_settings', function (Blueprint $table): void {
            $table->string('mode')->default('manual');
            $table->string('provider')->default('none');
            $table->json('config_json')->default('{}');
        });

        DB::table('tax_settings')->orderBy('store_id')->each(function (object $settings): void {
            $legacyRates = json_decode((string) $settings->rates_json, true) ?: [];
            $taxRates = [];

            foreach ($legacyRates as $countryCode => $rate) {
                if (is_numeric($rate)) {
                    $taxRates[] = ['country_code' => strtoupper((string) $countryCode), 'rate' => (int) $rate];
                }
            }

            DB::table('tax_settings')->where('store_id', $settings->store_id)->update([
                'config_json' => json_encode([
                    'default_tax_rate' => (int) $settings->default_rate,
                    'tax_rates' => $taxRates,
                    'fallback' => 'allow',
                ], JSON_THROW_ON_ERROR),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_settings', function (Blueprint $table) {
            $table->dropColumn(['mode', 'provider', 'config_json']);
        });
    }
};
