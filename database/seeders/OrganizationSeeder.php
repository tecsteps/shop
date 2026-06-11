<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Seed the demo organization.
     */
    public function run(): void
    {
        Organization::query()->updateOrCreate(
            ['billing_email' => 'billing@acme.test'],
            ['name' => 'Acme Corp'],
        );
    }
}
