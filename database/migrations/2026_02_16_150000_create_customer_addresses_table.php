<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->text('first_name');
            $table->text('last_name');
            $table->text('company')->nullable();
            $table->text('address1');
            $table->text('address2')->nullable();
            $table->text('city');
            $table->text('province')->nullable();
            $table->text('province_code')->nullable();
            $table->text('country');
            $table->text('country_code');
            $table->text('zip');
            $table->text('phone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
