<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_installations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->text('scopes_json')->default('[]');
            $table->string('status')->default('active');
            $table->timestamp('installed_at')->nullable();

            $table->unique(['store_id', 'app_id'], 'idx_app_installations_store_app');
            $table->index('store_id', 'idx_app_installations_store_id');
            $table->index('app_id', 'idx_app_installations_app_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_installations');
    }
};
