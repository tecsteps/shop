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
        Schema::create('data_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('orders');
            $table->string('format', 20)->default('csv');
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->text('filters_json')->default('{}');
            $table->unsignedInteger('row_count')->default(0);
            $table->string('storage_key')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('download_expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index('store_id');
            $table->index(['store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_exports');
    }
};
