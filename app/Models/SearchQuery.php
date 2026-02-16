<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchQuery extends Model
{
    use BelongsToStore;

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'query',
        'results_count',
        'customer_id',
        'session_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'results_count' => 'integer',
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
