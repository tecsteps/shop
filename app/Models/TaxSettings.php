<?php

namespace App\Models;

use App\Enums\TaxMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSettings extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'store_id';

    protected $fillable = [
        'store_id',
        'mode',
        'provider',
        'rate',
        'prices_include_tax',
        'tax_name',
        'is_active',
        'config_json',
    ];

    protected function casts(): array
    {
        return [
            'mode' => TaxMode::class,
            'rate' => 'integer',
            'prices_include_tax' => 'boolean',
            'is_active' => 'boolean',
            'config_json' => 'array',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
