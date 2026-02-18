<?php

namespace Database\Seeders;

use App\Enums\StoreUserRole;
use App\Models\Organization;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::first() ?? Organization::factory()->create([
            'name' => 'Acme Corp',
            'billing_email' => 'billing@acme.com',
        ]);

        $store = Store::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Acme Fashion',
            'handle' => 'acme-fashion',
        ]);

        StoreDomain::factory()->create([
            'store_id' => $store->id,
            'hostname' => 'acme-fashion.test',
            'is_primary' => true,
        ]);

        StoreSettings::factory()->create([
            'store_id' => $store->id,
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@acme.com',
            'password' => Hash::make('password'),
        ]);

        $store->users()->attach($admin, ['role' => StoreUserRole::Owner]);
    }
}
