<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsDaily extends Model
{
    use BelongsToStore, HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analytics_daily';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'store_id' => 'integer',
            'orders_count' => 'integer',
            'revenue_amount' => 'integer',
            'aov_amount' => 'integer',
            'visits_count' => 'integer',
            'add_to_cart_count' => 'integer',
            'checkout_started_count' => 'integer',
            'checkout_completed_count' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
