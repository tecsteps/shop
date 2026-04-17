<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('price_amount')->default(0);
            $table->integer('compare_at_amount')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('weight_g')->nullable();
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('position')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('product_id', 'idx_product_variants_product_id');
            $table->index('sku', 'idx_product_variants_sku');
            $table->index('barcode', 'idx_product_variants_barcode');
            $table->index(['product_id', 'position'], 'idx_product_variants_product_position');
            $table->index(['product_id', 'is_default'], 'idx_product_variants_product_default');
        });

        DB::statement("CREATE TRIGGER product_variants_status_check BEFORE INSERT ON product_variants FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('active','archived') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER product_variants_status_check_update BEFORE UPDATE ON product_variants FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('active','archived') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS product_variants_status_check');
        DB::statement('DROP TRIGGER IF EXISTS product_variants_status_check_update');
        Schema::dropIfExists('product_variants');
    }
};
