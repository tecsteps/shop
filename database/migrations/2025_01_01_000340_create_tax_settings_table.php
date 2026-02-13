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
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->text('mode')->default('manual');
            $table->text('provider')->default('none');
            $table->integer('prices_include_tax')->default(0);
            $table->text('config_json')->default('{}');
        });

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_tax_mode_insert
            BEFORE INSERT ON tax_settings
            BEGIN
                SELECT CASE
                    WHEN NEW.mode NOT IN ('manual', 'provider')
                    THEN RAISE(ABORT, 'Invalid tax mode')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_tax_mode_update
            BEFORE UPDATE ON tax_settings
            BEGIN
                SELECT CASE
                    WHEN NEW.mode NOT IN ('manual', 'provider')
                    THEN RAISE(ABORT, 'Invalid tax mode')
                END;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_tax_mode_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_tax_mode_update');
        Schema::dropIfExists('tax_settings');
    }
};
