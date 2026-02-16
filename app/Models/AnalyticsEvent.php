<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    use BelongsToStore;

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'type',
        'properties_json',
        'session_id',
        'customer_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
