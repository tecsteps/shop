<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('version')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('store_id', 'idx_themes_store_id');
            $table->index(['store_id', 'status'], 'idx_themes_store_status');
        });

        DB::statement("CREATE TRIGGER themes_status_check BEFORE INSERT ON themes FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('draft','published') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER themes_status_check_update BEFORE UPDATE ON themes FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('draft','published') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS themes_status_check');
        DB::statement('DROP TRIGGER IF EXISTS themes_status_check_update');
        Schema::dropIfExists('themes');
    }
};
