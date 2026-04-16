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
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('handle');
            $table->string('title');

            $table->unique(['store_id', 'handle'], 'idx_navigation_menus_store_handle');
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->nullOnDelete();
            $table->string('type');
            $table->string('label');
            $table->string('url')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->integer('position')->default(0);

            $table->index('menu_id', 'idx_navigation_items_menu_id');
            $table->index('parent_id', 'idx_navigation_items_parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigation_menus');
    }
};
