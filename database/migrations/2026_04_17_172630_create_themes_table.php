<?php

use App\Enums\ThemeStatus;
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
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('version')->nullable();
            $table->string('status')->default(ThemeStatus::Draft->value);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('store_id', 'idx_themes_store_id');
            $table->index(['store_id', 'status'], 'idx_themes_store_status');
        });

        $allowed = collect(ThemeStatus::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        DB::statement("CREATE TRIGGER themes_status_check_insert BEFORE INSERT ON themes FOR EACH ROW WHEN NEW.status NOT IN ({$allowed}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER themes_status_check_update BEFORE UPDATE ON themes FOR EACH ROW WHEN NEW.status NOT IN ({$allowed}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS themes_status_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS themes_status_check_update');
        Schema::dropIfExists('themes');
    }
};
