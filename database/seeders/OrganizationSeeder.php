<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    /**
     * Create the billing organization that owns every demo store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            Organization::updateOrCreate(
                ['name' => 'Acme Corp'],
                ['billing_email' => 'billing@acme.test'],
            );
        });
    }
}
