<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_installations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->foreignId('app_id')
                ->constrained('apps')
                ->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->text('settings_json')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['store_id', 'app_id'], 'idx_app_installations_store_app');
            $table->index('store_id', 'idx_app_installations_store_id');
        });

        DB::statement("CREATE TRIGGER app_installations_status_check BEFORE INSERT ON app_installations FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('active','paused','uninstalled') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER app_installations_status_check_update BEFORE UPDATE ON app_installations FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('active','paused','uninstalled') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS app_installations_status_check');
        DB::statement('DROP TRIGGER IF EXISTS app_installations_status_check_update');
        Schema::dropIfExists('app_installations');
    }
};
