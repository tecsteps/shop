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
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('title')->nullable();
            $table->string('type')->default('code');
            $table->string('value_type');
            $table->unsignedInteger('value_amount')->default(0);
            $table->string('status')->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('minimum_purchase_amount')->nullable();
            $table->text('rules_json')->nullable();
            $table->text('metadata')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'code'], 'idx_discounts_store_code');
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
