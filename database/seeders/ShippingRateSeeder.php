<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

class ShippingRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ShippingZone::withoutGlobalScopes()->get()->each(function (ShippingZone $zone): void {
            foreach ($this->rates() as $rate) {
                ShippingRate::withoutGlobalScopes()->updateOrCreate(
                    [
                        'zone_id' => $zone->getKey(),
                        'name' => $rate['name'],
                    ],
                    [
                        'type' => $rate['type'],
                        'config_json' => $rate['config_json'],
                        'is_active' => true,
                    ],
                );
            }
        });
    }

    /**
     * @return array<int, array{name: string, type: string, config_json: array<string, mixed>}>
     */
    private function rates(): array
    {
        return [
            ['name' => 'Standard Shipping', 'type' => 'flat', 'config_json' => ['amount' => 799]],
            ['name' => 'Express Shipping', 'type' => 'flat', 'config_json' => ['amount' => 1499]],
            [
                'name' => 'Free Shipping Over 75',
                'type' => 'price',
                'config_json' => [
                    'ranges' => [
                        ['min_amount' => 0, 'max_amount' => 7499, 'amount' => 799],
                        ['min_amount' => 7500, 'amount' => 0],
                    ],
                ],
            ],
        ];
    }
}
