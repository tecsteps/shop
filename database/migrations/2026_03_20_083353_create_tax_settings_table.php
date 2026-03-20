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
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->primary();
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
            $table->text('mode')->default('manual');
            $table->text('provider')->default('none');
            $table->integer('rate')->default(0);
            $table->integer('prices_include_tax')->default(0);
            $table->text('tax_name')->default('Tax');
            $table->integer('is_active')->default(1);
            $table->text('config_json')->default('{}');
        });

        DB::statement("CREATE TRIGGER tax_settings_mode_check BEFORE INSERT ON tax_settings
            BEGIN
                SELECT CASE WHEN NEW.mode NOT IN ('manual', 'provider')
                    THEN RAISE(ABORT, 'Invalid tax mode')
                END;
            END;");

        DB::statement("CREATE TRIGGER tax_settings_mode_check_update BEFORE UPDATE ON tax_settings
            BEGIN
                SELECT CASE WHEN NEW.mode NOT IN ('manual', 'provider')
                    THEN RAISE(ABORT, 'Invalid tax mode')
                END;
            END;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
