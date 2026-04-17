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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('version')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('store_id', 'idx_themes_store_id');
            $table->index(['store_id', 'status'], 'idx_themes_store_status');
        });

        Schema::create('theme_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained('themes')->cascadeOnDelete();
            $table->string('path');
            $table->string('storage_key');
            $table->string('sha256');
            $table->integer('byte_size')->default(0);

            $table->unique(['theme_id', 'path'], 'idx_theme_files_theme_path');
            $table->index('theme_id', 'idx_theme_files_theme_id');
        });

        Schema::create('theme_settings', function (Blueprint $table) {
            $table->foreignId('theme_id')->primary()->constrained('themes')->cascadeOnDelete();
            $table->text('settings_json')->default('{}');
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('body_html')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_pages_store_handle');
            $table->index('store_id', 'idx_pages_store_id');
            $table->index(['store_id', 'status'], 'idx_pages_store_status');
        });

        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('handle');
            $table->string('title');
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_navigation_menus_store_handle');
            $table->index('store_id', 'idx_navigation_menus_store_id');
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->enum('type', ['link', 'page', 'collection', 'product'])->default('link');
            $table->string('label');
            $table->string('url')->nullable();
            $table->foreignId('resource_id')->nullable();
            $table->integer('position')->default(0);

            $table->index('menu_id', 'idx_navigation_items_menu_id');
            $table->index(['menu_id', 'position'], 'idx_navigation_items_menu_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigation_menus');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('theme_settings');
        Schema::dropIfExists('theme_files');
        Schema::dropIfExists('themes');
    }
};
