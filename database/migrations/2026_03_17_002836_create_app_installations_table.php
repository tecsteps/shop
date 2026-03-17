<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_id')->constrained()->cascadeOnDelete();
            $table->text('scopes_json')->default('[]');
            $table->text('status')->default('active');
            $table->text('installed_at')->nullable();

            $table->unique(['store_id', 'app_id'], 'idx_app_installations_store_app');
            $table->index('store_id', 'idx_app_installations_store_id');
            $table->index('app_id', 'idx_app_installations_app_id');
        });

        DB::statement("CREATE TRIGGER check_app_installations_status INSERT ON app_installations
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('active', 'suspended', 'uninstalled')
                    THEN RAISE(ABORT, 'Invalid app installation status')
                END;
            END");

        DB::statement("CREATE TRIGGER check_app_installations_status_update UPDATE OF status ON app_installations
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('active', 'suspended', 'uninstalled')
                    THEN RAISE(ABORT, 'Invalid app installation status')
                END;
            END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_app_installations_status');
        DB::statement('DROP TRIGGER IF EXISTS check_app_installations_status_update');
        Schema::dropIfExists('app_installations');
    }
};
