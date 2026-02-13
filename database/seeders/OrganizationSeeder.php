<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    /**
     * Seed the organizations table.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            Organization::firstOrCreate(
                ['billing_email' => 'billing@acme.test'],
                ['name' => 'Acme Corp'],
            );
        });
    }
}
