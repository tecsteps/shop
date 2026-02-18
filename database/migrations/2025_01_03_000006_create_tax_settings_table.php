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
            $table->string('mode')->default('manual');
            $table->unsignedInteger('rate_percent')->default(0);
            $table->boolean('prices_include_tax')->default(false);
            $table->boolean('tax_shipping')->default(false);
            $table->string('provider_name')->nullable();
            $table->text('provider_config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
