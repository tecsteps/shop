<?php

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
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable()->comment('FK to customers added in Phase 6');
            $table->string('session_id')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('cart_version')->default(1);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('store_id', 'idx_carts_store_id');
            $table->index('customer_id', 'idx_carts_customer_id');
            $table->index(['store_id', 'status'], 'idx_carts_store_status');
            $table->index('session_id', 'idx_carts_session_id');
        });

        DB::statement("CREATE TRIGGER carts_status_check BEFORE INSERT ON carts FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('active','converted','abandoned') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER carts_status_check_update BEFORE UPDATE ON carts FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('active','converted','abandoned') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS carts_status_check');
        DB::statement('DROP TRIGGER IF EXISTS carts_status_check_update');
        Schema::dropIfExists('carts');
    }
};
