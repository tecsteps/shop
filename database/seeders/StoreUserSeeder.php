<?php

namespace Database\Seeders;

use App\Enums\StoreUserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoreUserSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('slug', 'acme-fashion')->firstOrFail();
        $electronics = Store::where('slug', 'acme-electronics')->firstOrFail();

        $assignments = [
            ['email' => 'admin@acme.test', 'store' => $fashion, 'role' => StoreUserRole::Owner],
            ['email' => 'staff@acme.test', 'store' => $fashion, 'role' => StoreUserRole::Staff],
            ['email' => 'support@acme.test', 'store' => $fashion, 'role' => StoreUserRole::Support],
            ['email' => 'manager@acme.test', 'store' => $fashion, 'role' => StoreUserRole::Admin],
            ['email' => 'admin2@acme.test', 'store' => $electronics, 'role' => StoreUserRole::Owner],
        ];

        foreach ($assignments as $assignment) {
            $user = User::where('email', $assignment['email'])->firstOrFail();
            if (! $assignment['store']->users()->where('user_id', $user->id)->exists()) {
                $assignment['store']->users()->attach($user->id, [
                    'role' => $assignment['role']->value,
                ]);
            }
        }
    }
}
