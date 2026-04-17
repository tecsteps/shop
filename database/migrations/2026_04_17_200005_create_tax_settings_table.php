<?php

use App\Enums\TaxMode;
use App\Enums\TaxProviderType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_settings', function (Blueprint $table): void {
            $table->foreignId('store_id')->primary()->constrained('stores')->cascadeOnDelete();
            $table->string('mode')->default(TaxMode::Manual->value);
            $table->string('provider')->default(TaxProviderType::None->value);
            $table->unsignedTinyInteger('prices_include_tax')->default(0);
            $table->text('config_json')->default('{}');
        });

        $modes = collect(TaxMode::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        $providers = collect(TaxProviderType::values())->map(fn (string $v): string => "'".$v."'")->implode(',');

        DB::statement("CREATE TRIGGER tax_settings_mode_check_insert BEFORE INSERT ON tax_settings FOR EACH ROW WHEN NEW.mode NOT IN ({$modes}) BEGIN SELECT RAISE(ABORT, 'invalid mode'); END");
        DB::statement("CREATE TRIGGER tax_settings_mode_check_update BEFORE UPDATE ON tax_settings FOR EACH ROW WHEN NEW.mode NOT IN ({$modes}) BEGIN SELECT RAISE(ABORT, 'invalid mode'); END");
        DB::statement("CREATE TRIGGER tax_settings_provider_check_insert BEFORE INSERT ON tax_settings FOR EACH ROW WHEN NEW.provider NOT IN ({$providers}) BEGIN SELECT RAISE(ABORT, 'invalid provider'); END");
        DB::statement("CREATE TRIGGER tax_settings_provider_check_update BEFORE UPDATE ON tax_settings FOR EACH ROW WHEN NEW.provider NOT IN ({$providers}) BEGIN SELECT RAISE(ABORT, 'invalid provider'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS tax_settings_mode_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS tax_settings_mode_check_update');
        DB::statement('DROP TRIGGER IF EXISTS tax_settings_provider_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS tax_settings_provider_check_update');
        Schema::dropIfExists('tax_settings');
    }
};
