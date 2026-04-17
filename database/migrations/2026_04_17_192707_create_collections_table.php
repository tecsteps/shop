<?php

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
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
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('title');
            $table->string('handle');
            $table->text('description_html')->nullable();
            $table->string('type')->default(CollectionType::Manual->value);
            $table->string('status')->default(CollectionStatus::Active->value);
            $table->timestamps();

            $table->unique(['store_id', 'handle'], 'idx_collections_store_handle');
            $table->index('store_id', 'idx_collections_store_id');
            $table->index(['store_id', 'status'], 'idx_collections_store_status');
        });

        $statusAllowed = collect(CollectionStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        $typeAllowed = collect(CollectionType::values())->map(fn (string $v): string => "'".$v."'")->implode(',');

        DB::statement("CREATE TRIGGER collections_status_check_insert BEFORE INSERT ON collections FOR EACH ROW WHEN NEW.status NOT IN ({$statusAllowed}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER collections_status_check_update BEFORE UPDATE ON collections FOR EACH ROW WHEN NEW.status NOT IN ({$statusAllowed}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER collections_type_check_insert BEFORE INSERT ON collections FOR EACH ROW WHEN NEW.type NOT IN ({$typeAllowed}) BEGIN SELECT RAISE(ABORT, 'invalid type'); END");
        DB::statement("CREATE TRIGGER collections_type_check_update BEFORE UPDATE ON collections FOR EACH ROW WHEN NEW.type NOT IN ({$typeAllowed}) BEGIN SELECT RAISE(ABORT, 'invalid type'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS collections_status_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS collections_status_check_update');
        DB::statement('DROP TRIGGER IF EXISTS collections_type_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS collections_type_check_update');
        Schema::dropIfExists('collections');
    }
};
