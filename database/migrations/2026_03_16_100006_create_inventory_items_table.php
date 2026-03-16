<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->unique()->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->text('policy')->default('deny');

            $table->index('store_id', 'idx_inventory_items_store_id');
        });

        DB::statement("CREATE TRIGGER check_inventory_items_policy INSERT ON inventory_items
            BEGIN
                SELECT CASE WHEN NEW.policy NOT IN ('deny', 'continue')
                    THEN RAISE(ABORT, 'Invalid inventory policy')
                END;
            END");

        DB::statement("CREATE TRIGGER check_inventory_items_policy_update UPDATE OF policy ON inventory_items
            BEGIN
                SELECT CASE WHEN NEW.policy NOT IN ('deny', 'continue')
                    THEN RAISE(ABORT, 'Invalid inventory policy')
                END;
            END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_inventory_items_policy');
        DB::statement('DROP TRIGGER IF EXISTS check_inventory_items_policy_update');
        Schema::dropIfExists('inventory_items');
    }
};
