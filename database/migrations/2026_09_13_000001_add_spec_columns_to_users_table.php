<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add spec 01 columns to the Fortify-managed users table.
     *
     * The existing users table already provides: id, name, email (unique),
     * email_verified_at, password (= spec password_hash), remember_token,
     * two_factor_* columns and timestamps. Only status + last_login_at
     * are missing.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('active')->after('name');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'last_login_at']);
        });
    }
};
