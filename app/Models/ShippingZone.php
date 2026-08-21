<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'name', 'countries_json', 'regions_json'];

    protected function casts(): array
    {
        return ['countries_json' => 'array', 'regions_json' => 'array'];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function matchesCountry(string $countryCode): bool
    {
        $countries = $this->countries_json ?? [];

        return $countries === [] || in_array(strtoupper($countryCode), array_map('strtoupper', $countries), true);
    }
}
