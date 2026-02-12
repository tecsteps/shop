<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    use BelongsToStore, HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'name',
        'countries_json',
        'regions_json',
        'is_rest_of_world',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'countries_json' => 'array',
            'regions_json' => 'array',
            'is_rest_of_world' => 'boolean',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'zone_id');
    }
}
