<?php

use App\Enums\ShippingRateType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default(ShippingRateType::Flat->value);
            $table->text('config_json')->default('{}');
            $table->unsignedTinyInteger('is_active')->default(1);

            $table->index('zone_id', 'idx_shipping_rates_zone_id');
            $table->index(['zone_id', 'is_active'], 'idx_shipping_rates_zone_active');
        });

        $allowed = collect(ShippingRateType::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        DB::statement("CREATE TRIGGER shipping_rates_type_check_insert BEFORE INSERT ON shipping_rates FOR EACH ROW WHEN NEW.type NOT IN ({$allowed}) BEGIN SELECT RAISE(ABORT, 'invalid type'); END");
        DB::statement("CREATE TRIGGER shipping_rates_type_check_update BEFORE UPDATE ON shipping_rates FOR EACH ROW WHEN NEW.type NOT IN ({$allowed}) BEGIN SELECT RAISE(ABORT, 'invalid type'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS shipping_rates_type_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS shipping_rates_type_check_update');
        Schema::dropIfExists('shipping_rates');
    }
};
