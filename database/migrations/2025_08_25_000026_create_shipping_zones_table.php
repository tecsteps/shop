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
            $table->json('countries_json')->default('[]');
            $table->json('regions_json')->default('[]');
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};
