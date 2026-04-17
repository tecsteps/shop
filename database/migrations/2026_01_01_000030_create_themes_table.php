<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('version')->default('1.0.0');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status'], 'idx_themes_store_status');
        });

        Schema::create('theme_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('storage_key');
            $table->string('sha256', 64)->nullable();
            $table->integer('byte_size')->nullable();

            $table->unique(['theme_id', 'path'], 'idx_theme_files_theme_path');
        });

        Schema::create('theme_settings', function (Blueprint $table) {
            $table->foreignId('theme_id')->primary()->constrained()->cascadeOnDelete();
            $table->text('settings_json')->default('{}');
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
        Schema::dropIfExists('theme_files');
        Schema::dropIfExists('themes');
    }
};
