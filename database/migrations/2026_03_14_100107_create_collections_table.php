<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->text('title');
            $table->text('handle');
            $table->text('description_html')->nullable();
            $table->text('status')->default('draft');
            $table->text('image_url')->nullable();
            $table->text('sort_order')->default('manual');
            $table->text('published_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_collections_store_id_handle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
