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
        Schema::table('stores', function (Blueprint $table): void {
            $table->enum('status', ['active', 'suspended'])->default('active')->change();
            $table->string('default_currency', 3)->default('USD')->change();
        });

        Schema::table('store_domains', function (Blueprint $table): void {
            $table->enum('type', ['storefront', 'admin', 'api'])->default('storefront')->change();
        });

        Schema::table('store_users', function (Blueprint $table): void {
            $table->enum('role', ['owner', 'admin', 'staff', 'support'])->default('staff')->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->enum('status', ['active', 'disabled'])->default('active')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('status')->default('active')->change();
        });

        Schema::table('store_users', function (Blueprint $table): void {
            $table->string('role')->default('staff')->change();
        });

        Schema::table('store_domains', function (Blueprint $table): void {
            $table->string('type')->default('storefront')->change();
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->string('status')->default('active')->change();
            $table->string('default_currency', 3)->default('EUR')->change();
        });
    }
};
