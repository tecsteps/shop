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
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('scopes_json')->nullable();
            $table->string('type')->default('first_party');
            $table->timestamps();
        });

        DB::statement("CREATE TRIGGER apps_type_check BEFORE INSERT ON apps FOR EACH ROW BEGIN SELECT CASE WHEN NEW.type NOT IN ('first_party','third_party') THEN RAISE(ABORT, 'invalid type') END; END");
        DB::statement("CREATE TRIGGER apps_type_check_update BEFORE UPDATE ON apps FOR EACH ROW BEGIN SELECT CASE WHEN NEW.type NOT IN ('first_party','third_party') THEN RAISE(ABORT, 'invalid type') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS apps_type_check');
        DB::statement('DROP TRIGGER IF EXISTS apps_type_check_update');
        Schema::dropIfExists('apps');
    }
};
