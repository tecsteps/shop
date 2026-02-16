<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    /** @use HasFactory<\Database\Factories\ShippingZoneFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'countries_json',
        'regions_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'countries_json' => 'array',
            'regions_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return HasMany<ShippingRate, $this> */
    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'zone_id');
    }
}
