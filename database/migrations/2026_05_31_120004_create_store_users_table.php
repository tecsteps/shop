<?php

use App\Support\Database\SqliteEnumCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('store_users', function (Blueprint $table) {
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('staff');
            $table->timestamp('created_at')->nullable();
            // Not in the original schema spec, but required so the
            // belongsToMany pivot can use withTimestamps() cleanly (staff
            // management attaches/syncs roles). Kept nullable.
            $table->timestamp('updated_at')->nullable();

            $table->primary(['store_id', 'user_id']);
            $table->index('user_id', 'idx_store_users_user_id');
            $table->index(['store_id', 'role'], 'idx_store_users_role');
        });

        SqliteEnumCheck::add('store_users', 'role', ['owner', 'admin', 'staff', 'support']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_users');
    }
};
