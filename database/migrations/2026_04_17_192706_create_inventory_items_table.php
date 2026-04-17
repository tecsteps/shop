<?php

use App\Enums\InventoryPolicy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->string('policy')->default(InventoryPolicy::Deny->value);

            $table->unique('variant_id', 'idx_inventory_items_variant_id');
            $table->index('store_id', 'idx_inventory_items_store_id');
        });

        $allowed = collect(InventoryPolicy::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        DB::statement("CREATE TRIGGER inventory_items_policy_check_insert BEFORE INSERT ON inventory_items FOR EACH ROW WHEN NEW.policy NOT IN ({$allowed}) BEGIN SELECT RAISE(ABORT, 'invalid policy'); END");
        DB::statement("CREATE TRIGGER inventory_items_policy_check_update BEFORE UPDATE ON inventory_items FOR EACH ROW WHEN NEW.policy NOT IN ({$allowed}) BEGIN SELECT RAISE(ABORT, 'invalid policy'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS inventory_items_policy_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS inventory_items_policy_check_update');
        Schema::dropIfExists('inventory_items');
    }
};
