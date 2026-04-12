<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_users', function (Blueprint $table): void {
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('role')->default('staff');
            $table->timestamp('created_at')->nullable();

            $table->primary(['store_id', 'user_id']);
            $table->index('user_id', 'idx_store_users_user_id');
            $table->index(['store_id', 'role'], 'idx_store_users_role');
        });

        DB::statement("CREATE TRIGGER store_users_role_check BEFORE INSERT ON store_users FOR EACH ROW BEGIN SELECT CASE WHEN NEW.role NOT IN ('owner','admin','staff','support') THEN RAISE(ABORT, 'invalid role') END; END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS store_users_role_check');
        Schema::dropIfExists('store_users');
    }
};
