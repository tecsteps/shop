<?php

use App\Enums\VariantStatus;
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
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->unsignedBigInteger('price_amount')->default(0);
            $table->unsignedBigInteger('compare_at_amount')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('weight_g')->nullable();
            $table->unsignedTinyInteger('requires_shipping')->default(1);
            $table->unsignedTinyInteger('is_default')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->string('status')->default(VariantStatus::Active->value);
            $table->timestamps();

            $table->index('product_id', 'idx_product_variants_product_id');
            $table->index('sku', 'idx_product_variants_sku');
            $table->index('barcode', 'idx_product_variants_barcode');
            $table->index(['product_id', 'position'], 'idx_product_variants_product_position');
            $table->index(['product_id', 'is_default'], 'idx_product_variants_product_default');
        });

        $allowed = collect(VariantStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        DB::statement("CREATE TRIGGER product_variants_status_check_insert BEFORE INSERT ON product_variants FOR EACH ROW WHEN NEW.status NOT IN ({$allowed}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER product_variants_status_check_update BEFORE UPDATE ON product_variants FOR EACH ROW WHEN NEW.status NOT IN ({$allowed}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS product_variants_status_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS product_variants_status_check_update');
        Schema::dropIfExists('product_variants');
    }
};
