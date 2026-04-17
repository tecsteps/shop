<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_settings', function (Blueprint $table): void {
            $table->foreignId('store_id')
                ->primary()
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->text('synonyms_json')->nullable();
            $table->text('stop_words_json')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_settings');
    }
};
