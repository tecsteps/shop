<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('status')->default('active')->after('password');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->index('status', 'idx_users_status');
        });

        $statuses = "'active','disabled'";
        DB::statement("CREATE TRIGGER users_status_check_insert BEFORE INSERT ON users FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
        DB::statement("CREATE TRIGGER users_status_check_update BEFORE UPDATE ON users FOR EACH ROW WHEN NEW.status NOT IN ({$statuses}) BEGIN SELECT RAISE(ABORT, 'invalid status'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS users_status_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS users_status_check_update');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('idx_users_status');
            $table->dropColumn(['status', 'last_login_at']);
        });
    }
};
