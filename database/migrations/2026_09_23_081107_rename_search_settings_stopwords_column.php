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
        Schema::table('search_settings', function (Blueprint $table): void {
            $table->renameColumn('stopwords_json', 'stop_words_json');
            $table->string('index_status')->default('ready');
            $table->timestamp('last_reindex_at')->nullable();
            $table->unsignedInteger('last_reindex_duration_seconds')->nullable();
            $table->unsignedInteger('documents_count')->default(0);
            $table->unsignedInteger('pending_updates')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_settings', function (Blueprint $table): void {
            $table->renameColumn('stop_words_json', 'stopwords_json');
            $table->dropColumn([
                'index_status',
                'last_reindex_at',
                'last_reindex_duration_seconds',
                'documents_count',
                'pending_updates',
            ]);
        });
    }
};
