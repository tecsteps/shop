<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
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

        DB::statement("CREATE TRIGGER product_media_type_check BEFORE INSERT ON product_media FOR EACH ROW BEGIN SELECT CASE WHEN NEW.type NOT IN ('image','video') THEN RAISE(ABORT, 'invalid type') END; END");
        DB::statement("CREATE TRIGGER product_media_type_check_update BEFORE UPDATE ON product_media FOR EACH ROW BEGIN SELECT CASE WHEN NEW.type NOT IN ('image','video') THEN RAISE(ABORT, 'invalid type') END; END");
        DB::statement("CREATE TRIGGER product_media_status_check BEFORE INSERT ON product_media FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('processing','ready','failed') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER product_media_status_check_update BEFORE UPDATE ON product_media FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('processing','ready','failed') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS product_media_type_check');
        DB::statement('DROP TRIGGER IF EXISTS product_media_type_check_update');
        DB::statement('DROP TRIGGER IF EXISTS product_media_status_check');
        DB::statement('DROP TRIGGER IF EXISTS product_media_status_check_update');
        Schema::dropIfExists('product_media');
    }
};
