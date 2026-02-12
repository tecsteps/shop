<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreUserSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

        $assignments = [
            ['email' => 'admin@acme.test', 'store_id' => $fashion->id, 'role' => 'owner'],
            ['email' => 'staff@acme.test', 'store_id' => $fashion->id, 'role' => 'staff'],
            ['email' => 'support@acme.test', 'store_id' => $fashion->id, 'role' => 'support'],
            ['email' => 'manager@acme.test', 'store_id' => $fashion->id, 'role' => 'admin'],
            ['email' => 'admin2@acme.test', 'store_id' => $electronics->id, 'role' => 'owner'],
        ];

        foreach ($assignments as $assignment) {
            $user = User::where('email', $assignment['email'])->firstOrFail();

            DB::table('store_users')->updateOrInsert(
                ['store_id' => $assignment['store_id'], 'user_id' => $user->id],
                ['role' => $assignment['role'], 'created_at' => now()],
            );
        }
    }
}
