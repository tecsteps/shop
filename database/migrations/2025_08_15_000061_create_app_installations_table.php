<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->text('status')->default('active');
            $table->text('config_json')->default('{}');
            $table->timestamps();

            $table->unique(['store_id', 'app_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_installations');
    }
};
