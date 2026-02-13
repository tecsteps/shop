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
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->text('email');
            $table->text('password_hash')->nullable();
            $table->text('name')->nullable();
            $table->integer('marketing_opt_in')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'email'], 'customers_store_email_unique');
            $table->index('store_id', 'idx_customers_store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
