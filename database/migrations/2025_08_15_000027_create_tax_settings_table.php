<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->primary();
            $table->string('mode')->default('manual');
            $table->boolean('prices_include_tax')->default(false);
            $table->integer('tax_rate_basis_points')->default(0);
            $table->string('tax_name')->default('Tax');
            $table->boolean('shipping_taxable')->default(false);
            $table->text('provider_config_json')->default('{}');
            $table->timestamp('updated_at')->nullable();

            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
