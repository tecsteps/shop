<?php

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
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
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('type')->default(DiscountType::Code->value);
            $table->string('code')->nullable();
            $table->string('value_type');
            $table->integer('value_amount')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->text('rules_json')->default('{}');
            $table->string('status')->default(DiscountStatus::Active->value);
            $table->timestamps();

            $table->unique(['store_id', 'code'], 'idx_discounts_store_code');
            $table->index('store_id', 'idx_discounts_store_id');
            $table->index(['store_id', 'status'], 'idx_discounts_store_status');
            $table->index(['store_id', 'type'], 'idx_discounts_store_type');
        });

        $types = collect(DiscountType::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        $valueTypes = collect(DiscountValueType::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        $statuses = collect(DiscountStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');

        DB::statement("CREATE TRIGGER discounts_type_check_insert BEFORE INSERT ON discounts FOR EACH ROW WHEN NEW.type NOT IN ({$types}) BEGIN SELECT RAISE(ABORT, 'invalid type'); END");
        DB::statement("CREATE TRIGGER discounts_type_check_update BEFORE UPDATE ON discounts FOR EACH ROW WHEN NEW.type NOT IN ({$types}) BEGIN SELECT RAISE(ABORT, 'invalid type'); END");
        DB::statement("CREATE TRIGGER discounts_value_type_check_insert BEFORE INSERT ON discounts FOR EACH ROW WHEN NEW.value_type NOT IN ({$valueTypes}) BEGIN SELECT RAISE(ABORT, 'invalid value_type'); END");
        DB::statement("CREATE TRIGGER discounts_value_type_check_update BEFORE UPDATE ON discounts FOR EACH ROW WHEN NEW.value_type NOT IN ({$valueTypes}) BEGIN SELECT RAISE(ABORT, 'invalid value_type'); END");
        DB::statement("CREATE TRIGGER discounts_status_check_insert BEFORE INSERT ON discounts FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER discounts_status_check_update BEFORE UPDATE ON discounts FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
    }

    public function down(): void
    {
        foreach (['discounts_type_check_insert', 'discounts_type_check_update', 'discounts_value_type_check_insert', 'discounts_value_type_check_update', 'discounts_status_check_insert', 'discounts_status_check_update'] as $trigger) {
            DB::statement("DROP TRIGGER IF EXISTS {$trigger}");
        }
        Schema::dropIfExists('discounts');
    }
};
