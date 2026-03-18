<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDaily extends Model
{
    use BelongsToStore;

    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'analytics_daily';

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
     * Override for composite primary key.
     */
    protected function setKeysForSaveQuery($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('store_id', $this->getAttribute('store_id'))
            ->where('date', $this->getAttribute('date'));
    }
}
