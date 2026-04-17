<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_password_reset_tokens', function (Blueprint $table): void {
            $table->string('email');
            $table->unsignedBigInteger('store_id');
            $table->string('token');
            $table->timestamp('created_at')->nullable();

            $table->primary(['email', 'store_id']);
            $table->index('store_id', 'idx_customer_password_reset_tokens_store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_password_reset_tokens');
    }
};
