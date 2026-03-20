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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('variant_id')->unique('idx_inventory_items_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->text('policy')->default('deny');

            $table->index('store_id', 'idx_inventory_items_store_id');
        });

        DB::statement("CREATE TRIGGER inventory_items_policy_check BEFORE INSERT ON inventory_items
            BEGIN
                SELECT CASE WHEN NEW.policy NOT IN ('deny', 'continue')
                    THEN RAISE(ABORT, 'Invalid inventory policy')
                END;
            END;");

        DB::statement("CREATE TRIGGER inventory_items_policy_check_update BEFORE UPDATE ON inventory_items
            BEGIN
                SELECT CASE WHEN NEW.policy NOT IN ('deny', 'continue')
                    THEN RAISE(ABORT, 'Invalid inventory policy')
                END;
            END;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
