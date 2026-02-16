<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->text('method');
            $table->text('provider')->default('mock');
            $table->text('provider_payment_id')->nullable();
            $table->integer('amount');
            $table->text('currency')->default('USD');
            $table->text('status')->default('pending');
            $table->text('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
