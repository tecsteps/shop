<?php

use App\Enums\ProductStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->string('status')->default(ProductStatus::Draft->value);
            $table->text('description_html')->nullable();
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();
            $table->text('tags')->default('[]');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_products_store_handle');
            $table->index('store_id', 'idx_products_store_id');
            $table->index(['store_id', 'status'], 'idx_products_store_status');
            $table->index(['store_id', 'published_at'], 'idx_products_published_at');
            $table->index(['store_id', 'vendor'], 'idx_products_vendor');
            $table->index(['store_id', 'product_type'], 'idx_products_product_type');
        });

        $allowed = collect(ProductStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        DB::statement("CREATE TRIGGER products_status_check_insert BEFORE INSERT ON products FOR EACH ROW WHEN NEW.status NOT IN ({$allowed}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER products_status_check_update BEFORE UPDATE ON products FOR EACH ROW WHEN NEW.status NOT IN ({$allowed}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS products_status_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS products_status_check_update');
        Schema::dropIfExists('products');
    }
};
