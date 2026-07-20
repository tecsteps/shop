<?php

namespace Database\Seeders;

use App\Enums\StoreStatus;
use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSeeder extends Seeder
{
    /**
     * Create the two demo stores under "Acme Corp" (spec 07 §3.2).
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $organization = Organization::query()
                ->where('name', 'Acme Corp')
                ->firstOrFail();

            foreach (['acme-fashion' => 'Acme Fashion', 'acme-electronics' => 'Acme Electronics'] as $handle => $name) {
                Store::query()->updateOrCreate(
                    ['handle' => $handle],
                    [
                        'organization_id' => $organization->id,
                        'name' => $name,
                        'status' => StoreStatus::Active,
                        'default_currency' => 'EUR',
                        'default_locale' => 'en',
                        'timezone' => 'Europe/Berlin',
                    ],
                );
            }
        });
    }
}
