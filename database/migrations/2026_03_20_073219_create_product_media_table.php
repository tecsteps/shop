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
        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
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

        DB::statement("CREATE TRIGGER product_media_type_check BEFORE INSERT ON product_media
            BEGIN
                SELECT CASE WHEN NEW.type NOT IN ('image', 'video')
                    THEN RAISE(ABORT, 'Invalid media type')
                END;
            END;");

        DB::statement("CREATE TRIGGER product_media_type_check_update BEFORE UPDATE ON product_media
            BEGIN
                SELECT CASE WHEN NEW.type NOT IN ('image', 'video')
                    THEN RAISE(ABORT, 'Invalid media type')
                END;
            END;");

        DB::statement("CREATE TRIGGER product_media_status_check BEFORE INSERT ON product_media
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('processing', 'ready', 'failed')
                    THEN RAISE(ABORT, 'Invalid media status')
                END;
            END;");

        DB::statement("CREATE TRIGGER product_media_status_check_update BEFORE UPDATE ON product_media
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('processing', 'ready', 'failed')
                    THEN RAISE(ABORT, 'Invalid media status')
                END;
            END;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
