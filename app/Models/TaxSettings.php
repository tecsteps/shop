<?php

namespace App\Models;

use App\Enums\TaxMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSettings extends Model
{
    use HasFactory;

    /** @var string */
    protected $primaryKey = 'store_id';

    /** @var bool */
    public $incrementing = false;

    const CREATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'mode',
        'prices_include_tax',
        'tax_rate_basis_points',
        'tax_name',
        'shipping_taxable',
        'provider_config_json',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'mode' => TaxMode::class,
            'prices_include_tax' => 'boolean',
            'shipping_taxable' => 'boolean',
            'provider_config_json' => 'array',
            'tax_rate_basis_points' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
