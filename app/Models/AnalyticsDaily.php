<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $store_id
 * @property string $date
 * @property int $orders_count
 * @property int $revenue_amount
 * @property int $aov_amount
 * @property int $visits_count
 * @property int $add_to_cart_count
 * @property int $checkout_started_count
 * @property int $checkout_completed_count
 */
class AnalyticsDaily extends Model
{
    use BelongsToStore;

    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'analytics_daily';

    /** @phpstan-ignore property.defaultValue */
    protected $primaryKey = ['store_id', 'date'];

    protected $fillable = [
        'store_id',
        'date',
        'orders_count',
        'revenue_amount',
        'aov_amount',
        'visits_count',
        'add_to_cart_count',
        'checkout_started_count',
        'checkout_completed_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orders_count' => 'integer',
            'revenue_amount' => 'integer',
            'aov_amount' => 'integer',
            'visits_count' => 'integer',
            'add_to_cart_count' => 'integer',
            'checkout_started_count' => 'integer',
            'checkout_completed_count' => 'integer',
        ];
    }

    /**
     * Override getKeyForSaveQuery for composite primary keys.
     *
     * @return array<string, mixed>
     */
    protected function getKeyForSaveQuery(): array
    {
        return [
            'store_id' => $this->getAttribute('store_id'),
            'date' => $this->getAttribute('date'),
        ];
    }

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    protected function setKeysForSaveQuery($query)
    {
        return $query
            ->where('store_id', $this->getAttribute('store_id'))
            ->where('date', $this->getAttribute('date'));
    }
}
