<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->string('type')->default('code');
            $table->string('code')->nullable();
            $table->string('value_type');
            $table->integer('value_amount')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->text('rules_json')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->unique(['store_id', 'code'], 'idx_discounts_store_code');
            $table->index('store_id', 'idx_discounts_store_id');
            $table->index(['store_id', 'status'], 'idx_discounts_store_status');
            $table->index(['store_id', 'type'], 'idx_discounts_store_type');
        });

        DB::statement("CREATE TRIGGER discounts_type_check BEFORE INSERT ON discounts FOR EACH ROW BEGIN SELECT CASE WHEN NEW.type NOT IN ('code','automatic') THEN RAISE(ABORT, 'invalid type') END; END");
        DB::statement("CREATE TRIGGER discounts_type_check_update BEFORE UPDATE ON discounts FOR EACH ROW BEGIN SELECT CASE WHEN NEW.type NOT IN ('code','automatic') THEN RAISE(ABORT, 'invalid type') END; END");
        DB::statement("CREATE TRIGGER discounts_value_type_check BEFORE INSERT ON discounts FOR EACH ROW BEGIN SELECT CASE WHEN NEW.value_type NOT IN ('percent','fixed','free_shipping') THEN RAISE(ABORT, 'invalid value_type') END; END");
        DB::statement("CREATE TRIGGER discounts_value_type_check_update BEFORE UPDATE ON discounts FOR EACH ROW BEGIN SELECT CASE WHEN NEW.value_type NOT IN ('percent','fixed','free_shipping') THEN RAISE(ABORT, 'invalid value_type') END; END");
        DB::statement("CREATE TRIGGER discounts_status_check BEFORE INSERT ON discounts FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('draft','active','expired','disabled') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER discounts_status_check_update BEFORE UPDATE ON discounts FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('draft','active','expired','disabled') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS discounts_type_check');
        DB::statement('DROP TRIGGER IF EXISTS discounts_type_check_update');
        DB::statement('DROP TRIGGER IF EXISTS discounts_value_type_check');
        DB::statement('DROP TRIGGER IF EXISTS discounts_value_type_check_update');
        DB::statement('DROP TRIGGER IF EXISTS discounts_status_check');
        DB::statement('DROP TRIGGER IF EXISTS discounts_status_check_update');
        Schema::dropIfExists('discounts');
    }
};
