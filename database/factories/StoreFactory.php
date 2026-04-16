<?php

namespace Database\Factories;

use App\Enums\StoreStatus;
use App\Models\Organization;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'organization_id' => Organization::factory(),
            'name' => $name.' Store',
            'handle' => Str::slug($name).'-'.Str::random(4),
            'status' => StoreStatus::Active->value,
            'default_currency' => 'EUR',
            'default_locale' => 'en',
            'timezone' => 'Europe/Berlin',
        ];
    }
}
