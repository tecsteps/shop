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
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->text('name');
            $table->text('countries_json')->default('[]');
            $table->text('regions_json')->default('[]');

            $table->index('store_id', 'idx_shipping_zones_store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};
