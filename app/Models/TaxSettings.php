<?php

namespace App\Models;

use App\Enums\TaxMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSettings extends Model
{
    /** @use HasFactory<\Database\Factories\TaxSettingsFactory> */
    use HasFactory;

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'mode',
        'rate_basis_points',
        'tax_name',
        'prices_include_tax',
        'charge_tax_on_shipping',
    ];

    protected function casts(): array
    {
        return [
            'mode' => TaxMode::class,
            'rate_basis_points' => 'integer',
            'prices_include_tax' => 'boolean',
            'charge_tax_on_shipping' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
