<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('status')->default('active')->after('email');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');

            $table->index('status');
        });

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_user_status_insert
            BEFORE INSERT ON users
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('active', 'disabled')
                    THEN RAISE(ABORT, 'Invalid user status')
                END;
            END;
        ");

        DB::statement("
            CREATE TRIGGER IF NOT EXISTS check_user_status_update
            BEFORE UPDATE ON users
            BEGIN
                SELECT CASE
                    WHEN NEW.status NOT IN ('active', 'disabled')
                    THEN RAISE(ABORT, 'Invalid user status')
                END;
            END;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_user_status_insert');
        DB::statement('DROP TRIGGER IF EXISTS check_user_status_update');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'last_login_at']);
        });
    }
};
