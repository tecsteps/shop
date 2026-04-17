<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('currency')->default('USD');
            $table->integer('cart_version')->default(1);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('store_id', 'idx_carts_store_id');
            $table->index('customer_id', 'idx_carts_customer_id');
            $table->index(['store_id', 'status'], 'idx_carts_store_status');
        });

        DB::statement("CREATE TRIGGER check_carts_status_insert
            BEFORE INSERT ON carts
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('active', 'converted', 'abandoned')
                    THEN RAISE(ABORT, 'Invalid cart status')
                END;
            END;");

        DB::statement("CREATE TRIGGER check_carts_status_update
            BEFORE UPDATE ON carts
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('active', 'converted', 'abandoned')
                    THEN RAISE(ABORT, 'Invalid cart status')
                END;
            END;");
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
