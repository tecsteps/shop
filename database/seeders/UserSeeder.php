<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $users = [
                ['email' => 'admin@acme.test', 'name' => 'Admin User', 'last_login_at' => now()],
                ['email' => 'staff@acme.test', 'name' => 'Staff User', 'last_login_at' => now()->subDays(2)],
                ['email' => 'support@acme.test', 'name' => 'Support User', 'last_login_at' => now()->subDay()],
                ['email' => 'manager@acme.test', 'name' => 'Store Manager', 'last_login_at' => now()->subDay()],
                ['email' => 'admin2@acme.test', 'name' => 'Admin Two', 'last_login_at' => now()->subDay()],
            ];

            foreach ($users as $userData) {
                User::firstOrCreate(
                    ['email' => $userData['email']],
                    [
                        'name' => $userData['name'],
                        'password' => 'password',
                        'status' => 'active',
                        'last_login_at' => $userData['last_login_at'],
                    ],
                );
            }

            // Store-User assignments
            $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
            $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

            $assignments = [
                ['admin@acme.test', $fashion->id, 'owner'],
                ['staff@acme.test', $fashion->id, 'staff'],
                ['support@acme.test', $fashion->id, 'support'],
                ['manager@acme.test', $fashion->id, 'admin'],
                ['admin2@acme.test', $electronics->id, 'owner'],
            ];

            foreach ($assignments as [$email, $storeId, $role]) {
                $user = User::where('email', $email)->firstOrFail();

                DB::table('store_users')->insertOrIgnore([
                    'store_id' => $storeId,
                    'user_id' => $user->id,
                    'role' => $role,
                    'created_at' => now(),
                ]);
            }
        });
    }
}
