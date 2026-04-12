<?php

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
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->string('status')->default('draft');
            $table->text('description_html')->nullable();
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();
            $table->json('tags')->default(DB::raw("('[]')"));
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_products_store_handle');
            $table->index('store_id', 'idx_products_store_id');
            $table->index(['store_id', 'status'], 'idx_products_store_status');
            $table->index(['store_id', 'published_at'], 'idx_products_published_at');
            $table->index(['store_id', 'vendor'], 'idx_products_vendor');
            $table->index(['store_id', 'product_type'], 'idx_products_product_type');
        });

        DB::statement("CREATE TRIGGER products_status_check BEFORE INSERT ON products FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('draft','active','archived') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER products_status_check_update BEFORE UPDATE ON products FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('draft','active','archived') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS products_status_check');
        DB::statement('DROP TRIGGER IF EXISTS products_status_check_update');
        Schema::dropIfExists('products');
    }
};
