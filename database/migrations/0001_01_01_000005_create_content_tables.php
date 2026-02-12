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
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('version')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
        });

        Schema::create('theme_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('storage_key');
            $table->string('sha256');
            $table->integer('byte_size')->default(0);
            $table->timestamps();

            $table->unique(['theme_id', 'path']);
            $table->index('theme_id');
        });

        Schema::create('theme_settings', function (Blueprint $table) {
            $table->foreignId('theme_id')->primary()->constrained()->cascadeOnDelete();
            $table->json('settings_json')->default('{}');
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('body_html')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'handle']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('handle');
            $table->string('title');
            $table->timestamps();

            $table->unique(['store_id', 'handle']);
            $table->index('store_id');
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->string('type')->default('link');
            $table->string('label');
            $table->string('url')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

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
        Schema::dropIfExists('navigation_menus');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('theme_settings');
        Schema::dropIfExists('theme_files');
        Schema::dropIfExists('themes');
    }
};
