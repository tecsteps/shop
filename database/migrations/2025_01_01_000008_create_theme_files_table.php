<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('storage_key');
            $table->string('sha256');
            $table->unsignedInteger('byte_size')->default(0);

            $table->unique(['theme_id', 'path'], 'idx_theme_files_theme_path');
            $table->index('theme_id', 'idx_theme_files_theme_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_files');
    }
};
