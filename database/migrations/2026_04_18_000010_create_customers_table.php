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
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('state')->default('active');
            $table->boolean('accepts_marketing')->default(false);
            $table->text('tags_json')->nullable();
            $table->text('metadata_json')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'email']);
            $table->index('store_id');
            $table->index('email');
        });

        Schema::create('customer_password_reset_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('email');
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            $table->unique(['store_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_password_reset_tokens');
        Schema::dropIfExists('customers');
    }
};
