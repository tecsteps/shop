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
            $table->string('method');
            $table->string('status')->default('pending');
            $table->integer('amount')->default(0);
            $table->string('currency')->default('EUR');
            $table->string('reference')->nullable();
            $table->text('gateway_response_json')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
