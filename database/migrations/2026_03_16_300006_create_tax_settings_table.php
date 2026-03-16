<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->primary();
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
            $table->text('mode')->default('manual');
            $table->text('provider')->default('none');
            $table->integer('prices_include_tax')->default(0);
            $table->text('config_json')->default('{}');
        });

        DB::statement("CREATE TRIGGER check_tax_settings_mode INSERT ON tax_settings
            BEGIN
                SELECT CASE WHEN NEW.mode NOT IN ('manual', 'provider')
                    THEN RAISE(ABORT, 'Invalid tax mode')
                END;
            END");

        DB::statement("CREATE TRIGGER check_tax_settings_mode_update UPDATE OF mode ON tax_settings
            BEGIN
                SELECT CASE WHEN NEW.mode NOT IN ('manual', 'provider')
                    THEN RAISE(ABORT, 'Invalid tax mode')
                END;
            END");

        DB::statement("CREATE TRIGGER check_tax_settings_provider INSERT ON tax_settings
            BEGIN
                SELECT CASE WHEN NEW.provider NOT IN ('stripe_tax', 'none')
                    THEN RAISE(ABORT, 'Invalid tax provider')
                END;
            END");

        DB::statement("CREATE TRIGGER check_tax_settings_provider_update UPDATE OF provider ON tax_settings
            BEGIN
                SELECT CASE WHEN NEW.provider NOT IN ('stripe_tax', 'none')
                    THEN RAISE(ABORT, 'Invalid tax provider')
                END;
            END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_tax_settings_mode');
        DB::statement('DROP TRIGGER IF EXISTS check_tax_settings_mode_update');
        DB::statement('DROP TRIGGER IF EXISTS check_tax_settings_provider');
        DB::statement('DROP TRIGGER IF EXISTS check_tax_settings_provider_update');
        Schema::dropIfExists('tax_settings');
    }
};
