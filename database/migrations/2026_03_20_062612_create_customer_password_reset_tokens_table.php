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
        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->string('email');
            $table->unsignedBigInteger('store_id');
            $table->string('token');
            $table->timestamp('created_at')->nullable();

            $table->primary(['email', 'store_id']);
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_password_reset_tokens');
    }
};
