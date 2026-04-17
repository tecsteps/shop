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
            $table->string('type')->default('code');
            $table->string('code')->nullable();
            $table->string('value_type');
            $table->integer('value_amount');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->text('rules_json')->default('{}');
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->unique(['store_id', 'code'], 'idx_discounts_store_code');
            $table->index(['store_id', 'status'], 'idx_discounts_store_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
