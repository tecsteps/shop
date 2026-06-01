<?php

use App\Support\Database\SqliteEnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('type')->default('code');
            $table->string('code')->nullable();
            $table->string('value_type');
            $table->integer('value_amount')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->text('rules_json')->default('{}');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['store_id', 'code'], 'idx_discounts_store_code');
            $table->index('store_id', 'idx_discounts_store_id');
            $table->index(['store_id', 'status'], 'idx_discounts_store_status');
            $table->index(['store_id', 'type'], 'idx_discounts_store_type');
        });

        SqliteEnumCheck::add('discounts', 'type', ['code', 'automatic']);
        SqliteEnumCheck::add('discounts', 'value_type', ['fixed', 'percent', 'free_shipping']);
        SqliteEnumCheck::add('discounts', 'status', ['draft', 'active', 'expired', 'disabled']);
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
