<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsDaily extends Model
{
    protected $table = 'analytics_daily';

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['store_id', 'date', 'orders_count', 'revenue_amount', 'aov_amount', 'visits_count', 'add_to_cart_count', 'checkout_started_count'];
}
