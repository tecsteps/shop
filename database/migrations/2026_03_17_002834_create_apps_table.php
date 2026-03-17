<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('status')->default('active');
            $table->timestamp('created_at')->nullable();

            $table->index('status', 'idx_apps_status');
        });

        DB::statement("CREATE TRIGGER check_apps_status INSERT ON apps
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('active', 'disabled')
                    THEN RAISE(ABORT, 'Invalid app status')
                END;
            END");

        DB::statement("CREATE TRIGGER check_apps_status_update UPDATE OF status ON apps
            BEGIN
                SELECT CASE WHEN NEW.status NOT IN ('active', 'disabled')
                    THEN RAISE(ABORT, 'Invalid app status')
                END;
            END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_apps_status');
        DB::statement('DROP TRIGGER IF EXISTS check_apps_status_update');
        Schema::dropIfExists('apps');
    }
};
