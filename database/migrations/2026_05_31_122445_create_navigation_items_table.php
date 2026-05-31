<?php

use App\Support\Database\SqliteEnumCheck;
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
            // Self-referencing parent for one level of dropdown submenus (see
            // storefront header spec). Top-level items have a null parent.
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->cascadeOnDelete();
            $table->string('type')->default('link');
            $table->string('label');
            $table->string('url')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->unsignedInteger('position')->default(0);

            $table->index('menu_id', 'idx_navigation_items_menu_id');
            $table->index(['menu_id', 'position'], 'idx_navigation_items_menu_position');
            $table->index('parent_id', 'idx_navigation_items_parent_id');
        });

        SqliteEnumCheck::add('navigation_items', 'type', ['link', 'page', 'collection', 'product']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
    }
};
