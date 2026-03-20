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
            $table->string('mode')->default('manual');
            $table->string('provider')->default('none');
            $table->boolean('prices_include_tax')->default(false);
            $table->json('config_json')->default('{}');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
