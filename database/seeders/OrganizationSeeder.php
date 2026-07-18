<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::query()->firstOrCreate(
            ['billing_email' => 'billing@example.com'],
            ['name' => 'Demo Organization'],
        );
    }
}
