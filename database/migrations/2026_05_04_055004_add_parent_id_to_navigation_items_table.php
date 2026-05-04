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
        Schema::table('navigation_items', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('menu_id')
                ->constrained('navigation_items')
                ->cascadeOnDelete();

            $table->index(['menu_id', 'parent_id', 'position'], 'idx_navigation_items_menu_parent_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('navigation_items', function (Blueprint $table) {
            $table->dropIndex('idx_navigation_items_menu_parent_position');
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
