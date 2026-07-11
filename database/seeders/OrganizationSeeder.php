<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(fn () => Organization::query()->updateOrCreate(
            ['billing_email' => 'billing@acme.test'],
            ['name' => 'Acme Corp'],
        ));
    }
}
