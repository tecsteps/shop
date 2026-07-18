<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_installation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->string('target_url');
            $table->string('secret');
            $table->string('status')->default('active');
            $table->integer('consecutive_failures')->default(0);
            $table->timestamps();
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
