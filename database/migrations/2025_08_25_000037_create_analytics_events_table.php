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
            $table->string('type');
            $table->string('session_id')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->json('properties_json')->default('{}');
            $table->string('client_event_id')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
            $table->index('store_id');
            $table->index(['store_id', 'type']);
            $table->index(['store_id', 'created_at']);
            $table->index('session_id');
            $table->index('customer_id');
            $table->unique(['store_id', 'client_event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
