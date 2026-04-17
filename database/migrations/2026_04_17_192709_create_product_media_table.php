<?php

use App\Enums\MediaStatus;
use App\Enums\MediaType;
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
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('type')->default(MediaType::Image->value);
            $table->string('storage_key');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->string('status')->default(MediaStatus::Processing->value);
            $table->timestamp('created_at')->nullable();

            $table->index('product_id', 'idx_product_media_product_id');
            $table->index(['product_id', 'position'], 'idx_product_media_product_position');
            $table->index('status', 'idx_product_media_status');
        });

        $typeAllowed = collect(MediaType::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        $statusAllowed = collect(MediaStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');

        DB::statement("CREATE TRIGGER product_media_type_check_insert BEFORE INSERT ON product_media FOR EACH ROW WHEN NEW.type NOT IN ({$typeAllowed}) BEGIN SELECT RAISE(ABORT, 'invalid type'); END");
        DB::statement("CREATE TRIGGER product_media_type_check_update BEFORE UPDATE ON product_media FOR EACH ROW WHEN NEW.type NOT IN ({$typeAllowed}) BEGIN SELECT RAISE(ABORT, 'invalid type'); END");
        DB::statement("CREATE TRIGGER product_media_status_check_insert BEFORE INSERT ON product_media FOR EACH ROW WHEN NEW.status NOT IN ({$statusAllowed}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER product_media_status_check_update BEFORE UPDATE ON product_media FOR EACH ROW WHEN NEW.status NOT IN ({$statusAllowed}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS product_media_type_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS product_media_type_check_update');
        DB::statement('DROP TRIGGER IF EXISTS product_media_status_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS product_media_status_check_update');
        Schema::dropIfExists('product_media');
    }
};
