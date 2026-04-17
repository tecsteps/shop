<?php

use App\Enums\NavigationItemType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->string('type')->default(NavigationItemType::Link->value);
            $table->string('label');
            $table->string('url')->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->integer('position')->default(0);

            $table->index('menu_id', 'idx_navigation_items_menu_id');
            $table->index(['menu_id', 'position'], 'idx_navigation_items_menu_position');
        });

        $allowed = collect(NavigationItemType::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        DB::statement("CREATE TRIGGER navigation_items_type_check_insert BEFORE INSERT ON navigation_items FOR EACH ROW WHEN NEW.type NOT IN ({$allowed}) BEGIN SELECT RAISE(ABORT, 'invalid type'); END");
        DB::statement("CREATE TRIGGER navigation_items_type_check_update BEFORE UPDATE ON navigation_items FOR EACH ROW WHEN NEW.type NOT IN ({$allowed}) BEGIN SELECT RAISE(ABORT, 'invalid type'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS navigation_items_type_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS navigation_items_type_check_update');
        Schema::dropIfExists('navigation_items');
    }
};
