<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->text('name');
            $table->text('handle');
            $table->text('status')->default('active');
            $table->text('default_currency')->default('EUR');
            $table->timestamps();

            $table->unique('handle', 'idx_stores_handle');
            $table->index('organization_id', 'idx_stores_organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
