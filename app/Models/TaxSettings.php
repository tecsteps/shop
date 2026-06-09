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

    /**
     * The table is keyed by store_id (one-to-one with stores).
     */
    protected $table = 'tax_settings';

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'mode',
        'provider',
        'prices_include_tax',
        'config_json',
    ];

    /**
     * Get the attributes that should be cast.
     *
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

    /**
     * The configured manual tax rate in basis points (1900 = 19%).
     */
    public function defaultRateBasisPoints(): int
    {
        return (int) ($this->config_json['default_rate_bps'] ?? 0);
    }

    /**
     * Whether shipping is subject to tax (defaults to true).
     */
    public function shippingTaxable(): bool
    {
        return (bool) ($this->config_json['shipping_taxable'] ?? true);
    }

    /**
     * Display name for the tax line.
     */
    public function taxName(): string
    {
        return (string) ($this->config_json['tax_name'] ?? 'Tax');
    }
}
