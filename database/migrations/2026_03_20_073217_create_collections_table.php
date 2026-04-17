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
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->text('title');
            $table->text('handle');
            $table->text('description_html')->nullable();
            $table->text('type')->default('manual');
            $table->text('status')->default('active');
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_collections_store_handle');
            $table->index('store_id', 'idx_collections_store_id');
            $table->index(['store_id', 'status'], 'idx_collections_store_status');
        });

        DB::statement("CREATE TRIGGER collections_status_check BEFORE INSERT ON collections
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('draft', 'active', 'archived')
                    THEN RAISE(ABORT, 'Invalid collection status')
                END;
            END;");

        DB::statement("CREATE TRIGGER collections_status_check_update BEFORE UPDATE ON collections
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('draft', 'active', 'archived')
                    THEN RAISE(ABORT, 'Invalid collection status')
                END;
            END;");

        DB::statement("CREATE TRIGGER collections_type_check BEFORE INSERT ON collections
            BEGIN
                SELECT CASE WHEN NEW.type NOT IN ('manual', 'automated')
                    THEN RAISE(ABORT, 'Invalid collection type')
                END;
            END;");

        DB::statement("CREATE TRIGGER collections_type_check_update BEFORE UPDATE ON collections
            BEGIN
                SELECT CASE WHEN NEW.type NOT IN ('manual', 'automated')
                    THEN RAISE(ABORT, 'Invalid collection type')
                END;
            END;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
