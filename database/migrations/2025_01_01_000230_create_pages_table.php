<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->text('title');
            $table->text('handle');
            $table->text('body_html')->nullable();
            $table->text('status')->default('draft');
            $table->text('published_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_pages_store_handle');
            $table->index('store_id', 'idx_pages_store_id');
            $table->index(['store_id', 'status'], 'idx_pages_store_status');
        });

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_page_status_insert
            BEFORE INSERT ON pages
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('draft', 'published', 'archived')
                    THEN RAISE(ABORT, 'Invalid page status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_page_status_update
            BEFORE UPDATE ON pages
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('draft', 'published', 'archived')
                    THEN RAISE(ABORT, 'Invalid page status')
                END;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_page_status_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_page_status_update');
        Schema::dropIfExists('pages');
    }
};
