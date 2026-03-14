<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('flat');
            $table->text('config_json')->default('{}');
            $table->boolean('is_active')->default(true);

            $table->index('zone_id', 'idx_shipping_rates_zone_id');
            $table->index(['zone_id', 'is_active'], 'idx_shipping_rates_zone_active');
        });

        DB::statement("CREATE TRIGGER check_shipping_rates_type_insert
            BEFORE INSERT ON shipping_rates
            BEGIN
                SELECT CASE
                    WHEN NEW.type NOT IN ('flat', 'weight', 'price', 'carrier')
                    THEN RAISE(ABORT, 'Invalid shipping rate type')
                END;
            END;");

        DB::statement("CREATE TRIGGER check_shipping_rates_type_update
            BEFORE UPDATE ON shipping_rates
            BEGIN
                SELECT CASE
                    WHEN NEW.type NOT IN ('flat', 'weight', 'price', 'carrier')
                    THEN RAISE(ABORT, 'Invalid shipping rate type')
                END;
            END;");
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
