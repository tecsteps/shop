<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDaily extends Model
{
    /** @use HasFactory<\Database\Factories\AnalyticsDailyFactory> */
    use BelongsToStore, HasFactory;

    protected $table = 'analytics_daily';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
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
            'date' => 'date',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where('store_id', $this->getAttribute('store_id'))
            ->where('date', $this->getAttribute('date'));

        return $query;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function setKeysForSelectQuery($query)
    {
        return $this->setKeysForSaveQuery($query);
    }
}
