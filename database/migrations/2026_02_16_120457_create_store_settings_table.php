<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('store_name')->nullable();
            $table->string('store_email')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('weight_unit')->default('kg');
            $table->string('currency')->default('USD');
            $table->boolean('checkout_requires_account')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
