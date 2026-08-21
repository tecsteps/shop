<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Organization::query()->updateOrCreate(
            ['slug' => 'acme-corp'],
            ['name' => 'Acme Corp', 'billing_email' => 'billing@acme.test', 'status' => 'active'],
        );
    }
}
