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
            $table->string('title');
            $table->string('type')->default('code');
            $table->string('value_type')->default('percent');
            $table->integer('value_amount')->default(0);
            $table->integer('minimum_purchase_amount')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('times_used')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status')->default('draft');
            $table->text('applies_to_json')->default('{}');
            $table->timestamps();

            $table->unique(['store_id', 'code']);
            $table->index('store_id');
            $table->index(['store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
