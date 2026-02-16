<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->text('mode')->default('manual');
            $table->integer('rate_basis_points')->default(0);
            $table->text('tax_name')->default('Tax');
            $table->boolean('prices_include_tax')->default(false);
            $table->boolean('charge_tax_on_shipping')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
