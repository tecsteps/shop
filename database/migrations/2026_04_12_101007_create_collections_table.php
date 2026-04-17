<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('description_html')->nullable();
            $table->string('type')->default('manual');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_collections_store_handle');
            $table->index('store_id', 'idx_collections_store_id');
            $table->index(['store_id', 'status'], 'idx_collections_store_status');
        });

        DB::statement("CREATE TRIGGER collections_type_check BEFORE INSERT ON collections FOR EACH ROW BEGIN SELECT CASE WHEN NEW.type NOT IN ('manual','automated') THEN RAISE(ABORT, 'invalid type') END; END");
        DB::statement("CREATE TRIGGER collections_type_check_update BEFORE UPDATE ON collections FOR EACH ROW BEGIN SELECT CASE WHEN NEW.type NOT IN ('manual','automated') THEN RAISE(ABORT, 'invalid type') END; END");
        DB::statement("CREATE TRIGGER collections_status_check BEFORE INSERT ON collections FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('draft','active','archived') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER collections_status_check_update BEFORE UPDATE ON collections FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('draft','active','archived') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS collections_type_check');
        DB::statement('DROP TRIGGER IF EXISTS collections_type_check_update');
        DB::statement('DROP TRIGGER IF EXISTS collections_status_check');
        DB::statement('DROP TRIGGER IF EXISTS collections_status_check_update');
        Schema::dropIfExists('collections');
    }
};
