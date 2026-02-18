<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::factory()->create([
            'name' => 'Acme Corp',
            'billing_email' => 'billing@acme.com',
        ]);
    }
}
