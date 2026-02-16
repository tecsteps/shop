<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('image');
            $table->text('storage_key');
            $table->text('alt_text')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('byte_size')->nullable();
            $table->integer('position')->default(0);
            $table->string('status')->default('processing');
            $table->timestamp('created_at')->nullable();

            $table->index(['product_id', 'position']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
