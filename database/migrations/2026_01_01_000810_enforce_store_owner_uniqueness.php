<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE UNIQUE INDEX idx_store_users_single_owner ON store_users (store_id) WHERE role = 'owner'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_store_users_single_owner');
    }
};
