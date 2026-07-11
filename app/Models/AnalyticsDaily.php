<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsDaily extends Model
{
    /** @use HasFactory<\Database\Factories\AnalyticsDailyFactory> */
    use BelongsToStore, HasFactory;

    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'analytics_daily';

    protected $fillable = [
        'store_id', 'date', 'orders_count', 'revenue_amount', 'aov_amount',
        'visits_count', 'add_to_cart_count', 'checkout_started_count',
        'checkout_completed_count',
    ];

    protected $attributes = [
        'orders_count' => 0,
        'revenue_amount' => 0,
        'aov_amount' => 0,
        'visits_count' => 0,
        'add_to_cart_count' => 0,
        'checkout_started_count' => 0,
        'checkout_completed_count' => 0,
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'orders_count' => 'integer',
            'revenue_amount' => 'integer',
            'aov_amount' => 'integer',
            'visits_count' => 'integer',
            'add_to_cart_count' => 'integer',
            'checkout_started_count' => 'integer',
            'checkout_completed_count' => 'integer',
        ];
    }
}
