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
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->text('name');
            $table->text('type')->default('flat');
            $table->text('config_json')->default('{}');
            $table->integer('is_active')->default(1);

            $table->index('zone_id', 'idx_shipping_rates_zone_id');
            $table->index(['zone_id', 'is_active'], 'idx_shipping_rates_zone_active');
        });

        DB::statement("CREATE TRIGGER shipping_rates_type_check BEFORE INSERT ON shipping_rates
            BEGIN
                SELECT CASE WHEN NEW.type NOT IN ('flat', 'weight', 'price', 'carrier')
                    THEN RAISE(ABORT, 'Invalid shipping rate type')
                END;
            END;");

        DB::statement("CREATE TRIGGER shipping_rates_type_check_update BEFORE UPDATE ON shipping_rates
            BEGIN
                SELECT CASE WHEN NEW.type NOT IN ('flat', 'weight', 'price', 'carrier')
                    THEN RAISE(ABORT, 'Invalid shipping rate type')
                END;
            END;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
