<?php

namespace App\Models;

use App\Enums\ShippingRateType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    /** @use HasFactory<\Database\Factories\ShippingRateFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'zone_id',
        'name',
        'type',
        'config_json',
        'is_active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'flat',
        'config_json' => '{}',
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('current_store', function (Builder $builder): void {
            if (! app()->bound('current_store')) {
                return;
            }

            $store = app('current_store');

            if (! $store instanceof Store) {
                return;
            }

            $builder->whereHas('zone', function (Builder $query) use ($store): void {
                $query
                    ->withoutGlobalScopes()
                    ->where('store_id', $store->getKey());
            });
        });
    }

    /**
     * @return BelongsTo<ShippingZone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ShippingRateType::class,
            'config_json' => 'array',
            'is_active' => 'bool',
        ];
    }
}
