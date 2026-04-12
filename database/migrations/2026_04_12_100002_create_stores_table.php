<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('handle')->unique('idx_stores_handle');
            $table->string('status')->default('active');
            $table->string('default_currency')->default('USD');
            $table->string('default_locale')->default('en');
            $table->string('timezone')->default('UTC');
            $table->timestamps();

            $table->index('organization_id', 'idx_stores_organization_id');
            $table->index('status', 'idx_stores_status');
        });

        DB::statement("CREATE TRIGGER stores_status_check BEFORE INSERT ON stores FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('active','suspended') THEN RAISE(ABORT, 'invalid status') END; END");
        DB::statement("CREATE TRIGGER stores_status_check_update BEFORE UPDATE ON stores FOR EACH ROW BEGIN SELECT CASE WHEN NEW.status NOT IN ('active','suspended') THEN RAISE(ABORT, 'invalid status') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS stores_status_check');
        DB::statement('DROP TRIGGER IF EXISTS stores_status_check_update');
        Schema::dropIfExists('stores');
    }
};
