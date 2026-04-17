<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->string('status')->default('active');
            $table->string('currency')->default('EUR');
            $table->unsignedInteger('cart_version')->default(1);
            $table->string('discount_code')->nullable();
            $table->text('note')->nullable();
            $table->text('metadata')->nullable();
            $table->timestamps();

            $table->index('store_id', 'idx_carts_store_id');
            $table->index('customer_id', 'idx_carts_customer_id');
            $table->index(['store_id', 'status'], 'idx_carts_store_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
