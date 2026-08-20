<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDaily extends Model
{
    use BelongsToStore;

    protected $table = 'analytics_daily';

    protected $fillable = ['store_id', 'date', 'orders_count', 'revenue_amount', 'aov_amount', 'visits_count', 'add_to_cart_count', 'checkout_started_count'];

    public $incrementing = false;

    public $timestamps = false;

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
