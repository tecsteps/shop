<?php

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

            $table->unique(['store_id', 'code']);
            $table->index('store_id', 'idx_discounts_store_id');
            $table->index(['store_id', 'status'], 'idx_discounts_store_status');
            $table->index(['store_id', 'type'], 'idx_discounts_store_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
