<?php

namespace Database\Seeders;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['email' => 'admin@acme.test', 'name' => 'Admin User'],
            ['email' => 'staff@acme.test', 'name' => 'Staff User'],
            ['email' => 'support@acme.test', 'name' => 'Support User'],
            ['email' => 'manager@acme.test', 'name' => 'Store Manager'],
            ['email' => 'admin2@acme.test', 'name' => 'Admin Two'],
        ];

        foreach ($users as $data) {
            User::create([
                'email' => $data['email'],
                'name' => $data['name'],
                'password' => Hash::make('password'),
            ]);
        }

        $fashion = Store::where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::where('handle', 'acme-electronics')->firstOrFail();

        $storeUsers = [
            ['admin@acme.test', $fashion->id, StoreUserRole::Owner],
            ['staff@acme.test', $fashion->id, StoreUserRole::Staff],
            ['support@acme.test', $fashion->id, StoreUserRole::Support],
            ['manager@acme.test', $fashion->id, StoreUserRole::Admin],
            ['admin2@acme.test', $electronics->id, StoreUserRole::Owner],
        ];

        foreach ($storeUsers as [$email, $storeId, $role]) {
            $user = User::where('email', $email)->first();
            $user->stores()->attach($storeId, ['role' => $role->value]);
        }
    }
}
