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
            $table->foreignId('store_id')->primary()->constrained('stores')->cascadeOnDelete();
            $table->string('mode')->default('manual');
            $table->string('provider')->default('none');
            $table->boolean('prices_include_tax')->default(false);
            $table->text('config_json')->default('{}');
        });

        DB::statement("CREATE TRIGGER check_tax_settings_enums_insert
            BEFORE INSERT ON tax_settings
            BEGIN
                SELECT CASE
                    WHEN NEW.mode NOT IN ('manual', 'provider')
                    THEN RAISE(ABORT, 'Invalid tax mode')
                END;
                SELECT CASE
                    WHEN NEW.provider NOT IN ('stripe_tax', 'none')
                    THEN RAISE(ABORT, 'Invalid tax provider')
                END;
            END;");

        DB::statement("CREATE TRIGGER check_tax_settings_enums_update
            BEFORE UPDATE ON tax_settings
            BEGIN
                SELECT CASE
                    WHEN NEW.mode NOT IN ('manual', 'provider')
                    THEN RAISE(ABORT, 'Invalid tax mode')
                END;
                SELECT CASE
                    WHEN NEW.provider NOT IN ('stripe_tax', 'none')
                    THEN RAISE(ABORT, 'Invalid tax provider')
                END;
            END;");
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
