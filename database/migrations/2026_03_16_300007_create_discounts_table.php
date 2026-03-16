<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->text('type')->default('code');
            $table->text('code')->nullable();
            $table->text('value_type');
            $table->integer('value_amount')->default(0);
            $table->text('starts_at');
            $table->text('ends_at')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->text('rules_json')->default('{}');
            $table->text('status')->default('active');
            $table->timestamps();

            $table->unique(['store_id', 'code'], 'idx_discounts_store_code');
            $table->index('store_id', 'idx_discounts_store_id');
            $table->index(['store_id', 'status'], 'idx_discounts_store_status');
            $table->index(['store_id', 'type'], 'idx_discounts_store_type');
        });

        DB::statement("CREATE TRIGGER check_discounts_type INSERT ON discounts
            BEGIN
                SELECT CASE WHEN NEW.type NOT IN ('code', 'automatic')
                    THEN RAISE(ABORT, 'Invalid discount type')
                END;
            END");

        DB::statement("CREATE TRIGGER check_discounts_type_update UPDATE OF type ON discounts
            BEGIN
                SELECT CASE WHEN NEW.type NOT IN ('code', 'automatic')
                    THEN RAISE(ABORT, 'Invalid discount type')
                END;
            END");

        DB::statement("CREATE TRIGGER check_discounts_value_type INSERT ON discounts
            BEGIN
                SELECT CASE WHEN NEW.value_type NOT IN ('fixed', 'percent', 'free_shipping')
                    THEN RAISE(ABORT, 'Invalid discount value type')
                END;
            END");

        DB::statement("CREATE TRIGGER check_discounts_value_type_update UPDATE OF value_type ON discounts
            BEGIN
                SELECT CASE WHEN NEW.value_type NOT IN ('fixed', 'percent', 'free_shipping')
                    THEN RAISE(ABORT, 'Invalid discount value type')
                END;
            END");

        DB::statement("CREATE TRIGGER check_discounts_status INSERT ON discounts
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('draft', 'active', 'expired', 'disabled')
                    THEN RAISE(ABORT, 'Invalid discount status')
                END;
            END");

        DB::statement("CREATE TRIGGER check_discounts_status_update UPDATE OF status ON discounts
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('draft', 'active', 'expired', 'disabled')
                    THEN RAISE(ABORT, 'Invalid discount status')
                END;
            END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_discounts_type');
        DB::statement('DROP TRIGGER IF EXISTS check_discounts_type_update');
        DB::statement('DROP TRIGGER IF EXISTS check_discounts_value_type');
        DB::statement('DROP TRIGGER IF EXISTS check_discounts_value_type_update');
        DB::statement('DROP TRIGGER IF EXISTS check_discounts_status');
        DB::statement('DROP TRIGGER IF EXISTS check_discounts_status_update');
        Schema::dropIfExists('discounts');
    }
};
