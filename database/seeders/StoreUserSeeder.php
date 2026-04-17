<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoreUserSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->first();
        $electronics = Store::where('handle', 'acme-electronics')->first();

        $assignments = [
            ['email' => 'admin@acme.test', 'store' => $fashion, 'role' => 'owner'],
            ['email' => 'staff@acme.test', 'store' => $fashion, 'role' => 'staff'],
            ['email' => 'support@acme.test', 'store' => $fashion, 'role' => 'support'],
            ['email' => 'manager@acme.test', 'store' => $fashion, 'role' => 'admin'],
            ['email' => 'admin2@acme.test', 'store' => $electronics, 'role' => 'owner'],
        ];

        foreach ($assignments as $a) {
            $user = User::where('email', $a['email'])->first();
            $a['store']->users()->attach($user->id, ['role' => $a['role']]);
        }
    }
}
