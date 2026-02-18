<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained()->cascadeOnDelete();
            $table->string('client_id')->unique();
            $table->text('client_secret_encrypted');
            $table->text('redirect_uris_json')->default('[]');

            $table->unique('client_id', 'idx_oauth_clients_client_id');
            $table->index('app_id', 'idx_oauth_clients_app_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_clients');
    }
};
