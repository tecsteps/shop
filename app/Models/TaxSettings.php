<?php

namespace App\Models;

use App\Enums\TaxMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSettings extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'store_id';

    protected $fillable = [
        'store_id',
        'mode',
        'provider',
        'prices_include_tax',
        'config_json',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => TaxMode::class,
            'prices_include_tax' => 'boolean',
            'config_json' => 'array',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
