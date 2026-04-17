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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->text('name');
            $table->text('version')->nullable();
            $table->text('status')->default('draft');
            $table->text('published_at')->nullable();
            $table->timestamps();

            $table->index('store_id', 'idx_themes_store_id');
            $table->index(['store_id', 'status'], 'idx_themes_store_status');
        });

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_theme_status_insert
            BEFORE INSERT ON themes
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('draft', 'published')
                    THEN RAISE(ABORT, 'Invalid theme status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_theme_status_update
            BEFORE UPDATE ON themes
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('draft', 'published')
                    THEN RAISE(ABORT, 'Invalid theme status')
                END;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_theme_status_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_theme_status_update');
        Schema::dropIfExists('themes');
    }
};
