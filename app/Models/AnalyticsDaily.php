<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDaily extends Model
{
    /** @use HasFactory<\Database\Factories\AnalyticsDailyFactory> */
    use BelongsToStore, HasFactory;

    public $timestamps = false;

    protected $table = 'analytics_daily';

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    /**
     * @var list<string>
     */
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
     * @var array<string, mixed>
     */
    protected $attributes = [
        'orders_count' => 0,
        'revenue_amount' => 0,
        'aov_amount' => 0,
        'visits_count' => 0,
        'add_to_cart_count' => 0,
        'checkout_started_count' => 0,
        'checkout_completed_count' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }
}
