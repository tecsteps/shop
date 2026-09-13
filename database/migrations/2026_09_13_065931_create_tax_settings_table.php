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
        Schema::disableForeignKeyConstraints();

        Schema::create('tax_settings', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete()->primary();
            $table->string('mode')->default('manual');
            $table->string('provider')->default('none');
            $table->integer('prices_include_tax')->default(0);
            $table->json('config_json')->default('{}');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
