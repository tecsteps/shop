<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    /**
     * Create the demo billing organization (spec 07 §3.1).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            Organization::query()->firstOrCreate(
                ['name' => 'Acme Corp'],
                ['billing_email' => 'billing@acme.test'],
            );
        });
    }
}
