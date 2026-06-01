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
        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('type')->default('image');
            $table->string('storage_key');
            $table->string('alt_text')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('byte_size')->nullable();
            $table->integer('position')->default(0);
            $table->string('status')->default('processing');
            $table->timestamp('created_at')->nullable();

            $table->index('product_id', 'idx_product_media_product_id');
            $table->index(['product_id', 'position'], 'idx_product_media_product_position');
            $table->index('status', 'idx_product_media_status');
        });

        SqliteEnumCheck::add('product_media', 'type', ['image', 'video']);
        SqliteEnumCheck::add('product_media', 'status', ['processing', 'ready', 'failed']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
