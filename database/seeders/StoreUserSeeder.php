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
        $store = Store::where('slug', 'acme-fashion')->firstOrFail();
        $user = User::where('email', 'admin@acme.test')->firstOrFail();

        $store->users()->attach($user->id, [
            'role' => StoreUserRole::Owner->value,
        ]);
    }
}
