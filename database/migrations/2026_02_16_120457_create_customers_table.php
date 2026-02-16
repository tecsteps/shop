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
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('password');
            $table->boolean('accepts_marketing')->default(false);
            $table->timestamps();

            $table->unique(['store_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
