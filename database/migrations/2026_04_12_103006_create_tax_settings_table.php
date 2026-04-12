<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_settings', function (Blueprint $table): void {
            $table->foreignId('store_id')
                ->primary()
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->string('mode')->default('manual');
            $table->string('provider')->nullable();
            $table->boolean('prices_include_tax')->default(false);
            $table->text('config_json')->default('{}');
            $table->timestamp('updated_at')->nullable();
        });

        DB::statement("CREATE TRIGGER tax_settings_mode_check BEFORE INSERT ON tax_settings FOR EACH ROW BEGIN SELECT CASE WHEN NEW.mode NOT IN ('manual','provider') THEN RAISE(ABORT, 'invalid mode') END; END");
        DB::statement("CREATE TRIGGER tax_settings_mode_check_update BEFORE UPDATE ON tax_settings FOR EACH ROW BEGIN SELECT CASE WHEN NEW.mode NOT IN ('manual','provider') THEN RAISE(ABORT, 'invalid mode') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS tax_settings_mode_check');
        DB::statement('DROP TRIGGER IF EXISTS tax_settings_mode_check_update');
        Schema::dropIfExists('tax_settings');
    }
};
