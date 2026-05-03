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
        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->enum('type', ['link', 'page', 'collection', 'product'])->default('link');
            $table->string('label');
            $table->string('url')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->integer('position')->default(0);

            $table->index('menu_id');
            $table->index(['menu_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
    }
};
