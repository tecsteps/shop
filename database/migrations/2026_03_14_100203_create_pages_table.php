<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->text('title');
            $table->text('handle');
            $table->text('content_html')->nullable();
            $table->text('status')->default('draft');
            $table->text('published_at')->nullable();
            $table->text('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_pages_store_id_handle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
