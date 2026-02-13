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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->text('title');
            $table->text('handle');
            $table->text('status')->default('draft');
            $table->text('description_html')->nullable();
            $table->text('vendor')->nullable();
            $table->text('product_type')->nullable();
            $table->text('tags')->default('[]');
            $table->text('published_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_products_store_handle');
            $table->index('store_id', 'idx_products_store_id');
            $table->index(['store_id', 'status'], 'idx_products_store_status');
            $table->index(['store_id', 'published_at'], 'idx_products_published_at');
            $table->index(['store_id', 'vendor'], 'idx_products_vendor');
            $table->index(['store_id', 'product_type'], 'idx_products_product_type');
        });

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_product_status_insert
            BEFORE INSERT ON products
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('draft', 'active', 'archived')
                    THEN RAISE(ABORT, 'Invalid product status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_product_status_update
            BEFORE UPDATE ON products
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('draft', 'active', 'archived')
                    THEN RAISE(ABORT, 'Invalid product status')
                END;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_product_status_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_product_status_update');
        Schema::dropIfExists('products');
    }
};
