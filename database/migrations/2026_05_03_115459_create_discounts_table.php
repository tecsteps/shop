<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['code', 'automatic'])->default('code');
            $table->string('code')->nullable();
            $table->enum('value_type', ['fixed', 'percent', 'free_shipping']);
            $table->integer('value_amount')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_count')->default(0);
            $table->text('rules_json')->default('{}');
            $table->enum('status', ['draft', 'active', 'expired', 'disabled'])->default('active');
            $table->timestamps();

            $table->unique(['store_id', 'code']);
            $table->index('store_id');
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
