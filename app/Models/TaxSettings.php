<?php

namespace App\Models;

use App\Enums\TaxMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSettings extends Model
{
    protected $table = 'tax_settings';

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['store_id', 'mode', 'provider', 'prices_include_tax', 'config_json'];

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

    public function defaultRate(): float
    {
        return (float) ($this->config_json['default_rate'] ?? 0.0);
    }
}
