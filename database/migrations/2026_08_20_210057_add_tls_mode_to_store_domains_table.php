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
        Schema::table('store_domains', function (Blueprint $table): void {
            $table->enum('tls_mode', ['managed', 'bring_your_own'])->default('managed')->after('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_domains', function (Blueprint $table): void {
            $table->dropColumn('tls_mode');
        });
    }
};
