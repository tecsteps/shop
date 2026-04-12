<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->longText('body_html')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_pages_store_handle');
            $table->index('store_id', 'idx_pages_store_id');
            $table->index(['store_id', 'status'], 'idx_pages_store_status');
        });

        DB::statement("CREATE TRIGGER pages_status_check BEFORE INSERT ON pages FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('draft','published','archived') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER pages_status_check_update BEFORE UPDATE ON pages FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('draft','published','archived') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS pages_status_check');
        DB::statement('DROP TRIGGER IF EXISTS pages_status_check_update');
        Schema::dropIfExists('pages');
    }
};
