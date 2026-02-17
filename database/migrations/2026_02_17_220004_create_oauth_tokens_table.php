<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_id')->constrained('app_installations')->cascadeOnDelete();
            $table->string('access_token_hash')->unique();
            $table->string('refresh_token_hash')->nullable();
            $table->string('expires_at');

            $table->index('installation_id', 'idx_oauth_tokens_installation_id');
            $table->index('expires_at', 'idx_oauth_tokens_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
    }
};
