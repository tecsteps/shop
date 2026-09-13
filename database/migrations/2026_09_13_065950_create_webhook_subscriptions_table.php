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
        Schema::disableForeignKeyConstraints();

        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_installation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('target_url');
            $table->text('signing_secret_encrypted');
            $table->string('status')->default('active');
            $table->index('store_id');
            $table->index(['store_id', 'event_type']);
            $table->index('app_installation_id');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
