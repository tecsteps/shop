<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('marketing_opt_in')->default(false);
            $table->string('status')->default('active');
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['store_id', 'email'], 'idx_customers_store_email');
            $table->index('store_id', 'idx_customers_store_id');
        });

        Schema::create('customer_password_reset_tokens', function (Blueprint $table) {
            $table->string('email');
            $table->foreignId('store_id');
            $table->string('token');
            $table->timestamp('created_at')->nullable();

            $table->primary(['email', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('customers');
    }
};
