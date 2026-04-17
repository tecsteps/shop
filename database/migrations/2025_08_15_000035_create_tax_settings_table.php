<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->foreignId('store_id')->primary()->constrained('stores')->cascadeOnDelete();
            $table->text('mode')->default('manual');
            $table->text('provider')->default('none');
            $table->integer('prices_include_tax')->default(0);
            $table->text('config_json')->default('{}');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
