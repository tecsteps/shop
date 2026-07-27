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
            $table->text('hostname');
            $table->text('type')->default('storefront');
            $table->integer('is_primary')->default(0);
            $table->text('tls_mode')->default('managed');
            $table->timestamp('created_at')->nullable();

            $table->unique('hostname', 'idx_store_domains_hostname');
            $table->index('store_id', 'idx_store_domains_store_id');
            $table->index(['store_id', 'is_primary'], 'idx_store_domains_store_primary');
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
