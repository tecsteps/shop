<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->primary();
            $table->text('synonyms_json')->default('[]');
            $table->text('stop_words_json')->default('[]');
            $table->text('boost_fields_json')->default('{}');
            $table->timestamp('updated_at')->nullable();

            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_settings');
    }
};
