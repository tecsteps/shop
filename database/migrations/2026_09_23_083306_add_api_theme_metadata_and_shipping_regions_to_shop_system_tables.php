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
        Schema::table('themes', function (Blueprint $table): void {
            $table->string('version')->nullable();
            $table->timestamp('published_at')->nullable();
        });

        Schema::table('shipping_zones', function (Blueprint $table): void {
            $table->json('regions')->default('[]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_zones', function (Blueprint $table): void {
            $table->dropColumn('regions');
        });

        Schema::table('themes', function (Blueprint $table): void {
            $table->dropColumn(['version', 'published_at']);
        });
    }
};
