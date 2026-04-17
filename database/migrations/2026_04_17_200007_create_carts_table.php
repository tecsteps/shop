<?php

use App\Enums\CartStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('currency', 3)->default('USD');
            $table->integer('cart_version')->default(1);
            $table->string('status')->default(CartStatus::Active->value);
            $table->timestamps();

            $table->index('store_id', 'idx_carts_store_id');
            $table->index('customer_id', 'idx_carts_customer_id');
            $table->index(['store_id', 'status'], 'idx_carts_store_status');
        });

        $statuses = collect(CartStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        DB::statement("CREATE TRIGGER carts_status_check_insert BEFORE INSERT ON carts FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER carts_status_check_update BEFORE UPDATE ON carts FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS carts_status_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS carts_status_check_update');
        Schema::dropIfExists('carts');
    }
};
