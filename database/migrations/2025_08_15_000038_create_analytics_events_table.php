<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->text('properties_json')->default('{}');
            $table->string('session_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('store_id');
            $table->index(['store_id', 'event_type']);
            $table->index(['store_id', 'created_at']);
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
