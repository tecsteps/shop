<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apps', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique('idx_apps_handle');
            $table->string('status')->default('active');
            $table->timestamp('created_at')->nullable();

            $table->index('status', 'idx_apps_status');
        });

        $statuses = "'active','disabled'";
        DB::statement("CREATE TRIGGER apps_status_check_insert BEFORE INSERT ON apps FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER apps_status_check_update BEFORE UPDATE ON apps FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS apps_status_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS apps_status_check_update');
        Schema::dropIfExists('apps');
    }
};
