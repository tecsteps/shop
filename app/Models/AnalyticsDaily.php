<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDaily extends Model
{
    /** @use HasFactory<\Database\Factories\AnalyticsDailyFactory> */
    use BelongsToStore, HasFactory;

    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'analytics_daily';

    protected $fillable = [
        'store_id', 'date', 'orders_count', 'revenue_amount', 'aov_amount', 'visits_count',
        'add_to_cart_count', 'checkout_started_count', 'checkout_completed_count',
    ];
}
