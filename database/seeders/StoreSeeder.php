<?php

namespace Database\Seeders;

use App\Enums\StoreStatus;
use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->where('name', 'Acme Corp')->firstOrFail();

        foreach ($this->stores() as $attributes) {
            Store::query()->updateOrCreate(
                ['handle' => $attributes['handle']],
                [...$attributes, 'organization_id' => $organization->getKey()]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function stores(): array
    {
        return [
            [
                'name' => 'Acme Fashion',
                'handle' => 'acme-fashion',
                'status' => StoreStatus::Active,
                'default_currency' => 'EUR',
                'default_locale' => 'en',
                'timezone' => 'Europe/Berlin',
            ],
            [
                'name' => 'Acme Electronics',
                'handle' => 'acme-electronics',
                'status' => StoreStatus::Active,
                'default_currency' => 'EUR',
                'default_locale' => 'en',
                'timezone' => 'Europe/Berlin',
            ],
        ];
    }
}
