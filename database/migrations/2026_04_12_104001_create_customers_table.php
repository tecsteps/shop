<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->string('email');
            $table->text('password_hash')->nullable();
            $table->string('name')->nullable();
            $table->boolean('marketing_opt_in')->default(false);
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['store_id', 'email'], 'idx_customers_store_email');
            $table->index('store_id', 'idx_customers_store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
