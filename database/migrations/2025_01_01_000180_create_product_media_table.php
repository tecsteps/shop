<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('type')->default('image');
            $table->text('storage_key');
            $table->text('alt_text')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->text('mime_type')->nullable();
            $table->integer('byte_size')->nullable();
            $table->integer('position')->default(0);
            $table->text('status')->default('processing');
            $table->text('created_at')->nullable();

            $table->index('product_id', 'idx_product_media_product_id');
            $table->index(['product_id', 'position'], 'idx_product_media_product_position');
            $table->index('status', 'idx_product_media_status');
        });

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_media_type_insert
            BEFORE INSERT ON product_media
            BEGIN
                SELECT CASE
                    WHEN NEW.type NOT IN ('image', 'video')
                    THEN RAISE(ABORT, 'Invalid media type')
                END;
                SELECT CASE
                    WHEN NEW.status NOT IN ('processing', 'ready', 'failed')
                    THEN RAISE(ABORT, 'Invalid media status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_media_type_update
            BEFORE UPDATE ON product_media
            BEGIN
                SELECT CASE
                    WHEN NEW.type NOT IN ('image', 'video')
                    THEN RAISE(ABORT, 'Invalid media type')
                END;
                SELECT CASE
                    WHEN NEW.status NOT IN ('processing', 'ready', 'failed')
                    THEN RAISE(ABORT, 'Invalid media status')
                END;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_media_type_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_media_type_update');
        Schema::dropIfExists('product_media');
    }
};
