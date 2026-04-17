<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->text('address_json')->default('{}');
            $table->unsignedTinyInteger('is_default')->default(0);

            $table->index('customer_id', 'idx_customer_addresses_customer_id');
            $table->index(['customer_id', 'is_default'], 'idx_customer_addresses_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
