<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDaily extends Model
{
    /** @use HasFactory<\Database\Factories\AnalyticsDailyFactory> */
    use BelongsToStore, HasFactory;

    /**
     * The table name (not the plural Eloquent would guess).
     *
     * @var string
     */
    protected $table = 'analytics_daily';

    /**
     * The table has a composite primary key (store_id + date).
     *
     * @var string|null
     */
    protected $primaryKey = null;

    /**
     * The composite primary key is not auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The table has no timestamp columns.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
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
     * Get the attributes that should be cast. The date column stays a plain
     * Y-m-d string (no cast) so range comparisons stay lexicographic.
     *
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
}
