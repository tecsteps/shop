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
        Schema::create('store_users', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('role')->default('staff');
            $table->timestamp('created_at')->nullable();

            $table->primary(['store_id', 'user_id']);
            $table->index('user_id', 'idx_store_users_user_id');
            $table->index(['store_id', 'role'], 'idx_store_users_role');
        });

        DB::statement("CREATE TRIGGER check_store_users_role INSERT ON store_users
            BEGIN
                SELECT CASE WHEN NEW.role NOT IN ('owner', 'admin', 'staff', 'support')
                    THEN RAISE(ABORT, 'Invalid store user role')
                END;
            END");

        DB::statement("CREATE TRIGGER check_store_users_role_update UPDATE OF role ON store_users
            BEGIN
                SELECT CASE WHEN NEW.role NOT IN ('owner', 'admin', 'staff', 'support')
                    THEN RAISE(ABORT, 'Invalid store user role')
                END;
            END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS check_store_users_role');
        DB::statement('DROP TRIGGER IF EXISTS check_store_users_role_update');
        Schema::dropIfExists('store_users');
    }
};
