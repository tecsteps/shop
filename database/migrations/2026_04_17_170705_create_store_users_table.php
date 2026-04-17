<?php

use App\Enums\StoreUserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_users', function (Blueprint $table): void {
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default(StoreUserRole::Staff->value);
            $table->timestamp('created_at')->nullable();

            $table->primary(['store_id', 'user_id']);
            $table->index('user_id', 'idx_store_users_user_id');
            $table->index(['store_id', 'role'], 'idx_store_users_role');
        });

        $roles = collect(StoreUserRole::values())->map(fn (string $v): string => "'".$v."'")->implode(',');
        DB::statement("CREATE TRIGGER store_users_role_check_insert BEFORE INSERT ON store_users FOR EACH ROW WHEN NEW.role NOT IN ({$roles}) BEGIN SELECT RAISE(ABORT, 'invalid role'); END");
        DB::statement("CREATE TRIGGER store_users_role_check_update BEFORE UPDATE ON store_users FOR EACH ROW WHEN NEW.role NOT IN ({$roles}) BEGIN SELECT RAISE(ABORT, 'invalid role'); END");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS store_users_role_check_insert');
        DB::statement('DROP TRIGGER IF EXISTS store_users_role_check_update');
        Schema::dropIfExists('store_users');
    }
};
