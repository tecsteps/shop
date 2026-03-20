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
        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->text('type')->default('link');
            $table->text('label');
            $table->text('url')->nullable();
            $table->integer('resource_id')->nullable();
            $table->integer('position')->default(0);

            $table->index('menu_id', 'idx_navigation_items_menu_id');
            $table->index(['menu_id', 'position'], 'idx_navigation_items_menu_position');
        });

        DB::statement("CREATE TRIGGER navigation_items_type_check BEFORE INSERT ON navigation_items
            BEGIN
                SELECT CASE WHEN NEW.type NOT IN ('link', 'page', 'collection', 'product')
                    THEN RAISE(ABORT, 'Invalid navigation item type')
                END;
            END;");

        DB::statement("CREATE TRIGGER navigation_items_type_check_update BEFORE UPDATE ON navigation_items
            BEGIN
                SELECT CASE WHEN NEW.type NOT IN ('link', 'page', 'collection', 'product')
                    THEN RAISE(ABORT, 'Invalid navigation item type')
                END;
            END;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
    }
};
