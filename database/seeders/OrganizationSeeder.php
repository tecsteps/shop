<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            Organization::firstOrCreate(
                ['billing_email' => 'billing@acme.test'],
                ['name' => 'Acme Corp'],
            );
        });
    }
}
