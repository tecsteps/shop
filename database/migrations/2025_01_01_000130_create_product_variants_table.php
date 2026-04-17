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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('sku')->nullable();
            $table->text('barcode')->nullable();
            $table->integer('price_amount')->default(0);
            $table->integer('compare_at_amount')->nullable();
            $table->text('currency')->default('USD');
            $table->integer('weight_g')->nullable();
            $table->integer('requires_shipping')->default(1);
            $table->integer('is_default')->default(0);
            $table->integer('position')->default(0);
            $table->text('status')->default('active');
            $table->timestamps();

            $table->index('product_id', 'idx_product_variants_product_id');
            $table->index('sku', 'idx_product_variants_sku');
            $table->index('barcode', 'idx_product_variants_barcode');
            $table->index(['product_id', 'position'], 'idx_product_variants_product_position');
            $table->index(['product_id', 'is_default'], 'idx_product_variants_product_default');
        });

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_variant_status_insert
            BEFORE INSERT ON product_variants
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('active', 'archived')
                    THEN RAISE(ABORT, 'Invalid variant status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_variant_status_update
            BEFORE UPDATE ON product_variants
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('active', 'archived')
                    THEN RAISE(ABORT, 'Invalid variant status')
                END;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_variant_status_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_variant_status_update');
        Schema::dropIfExists('product_variants');
    }
};
