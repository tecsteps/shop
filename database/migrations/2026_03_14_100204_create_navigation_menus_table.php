<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->text('name');
            $table->text('handle');
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_navigation_menus_store_id_handle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_menus');
    }
};
