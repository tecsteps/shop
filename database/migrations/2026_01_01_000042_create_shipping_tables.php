<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('countries_json')->default('[]');
            $table->text('regions_json')->default('[]');

            $table->index('store_id', 'idx_shipping_zones_store_id');
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('shipping_zones')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('flat');
            $table->text('config_json')->default('{}');
            $table->boolean('is_active')->default(true);

            $table->index('zone_id', 'idx_shipping_rates_zone_id');
        });

        Schema::create('tax_settings', function (Blueprint $table) {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('mode')->default('manual');
            $table->string('provider')->nullable();
            $table->boolean('prices_include_tax')->default(false);
            $table->text('config_json')->default('{}');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_zones');
    }
};
