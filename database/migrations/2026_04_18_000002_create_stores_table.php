<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('handle')->unique();
            $table->string('status')->default('active');
            $table->string('default_currency', 3)->default('USD');
            $table->string('default_locale', 10)->default('en');
            $table->string('timezone', 40)->default('UTC');
            $table->timestamps();
            $table->index('organization_id', 'idx_stores_organization_id');
            $table->index('status', 'idx_stores_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
