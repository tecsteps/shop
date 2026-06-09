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
        Schema::create('store_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('hostname')->unique();
            $table->enum('type', ['storefront', 'admin', 'api'])->default('storefront');
            $table->boolean('is_primary')->default(false);
            $table->enum('tls_mode', ['managed', 'bring_your_own'])->default('managed');
            $table->timestamp('created_at')->nullable();

            $table->index(['store_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_domains');
    }
};
