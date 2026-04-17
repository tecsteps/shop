<?php

namespace App\Models;

use App\Enums\TaxMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $store_id
 * @property TaxMode $mode
 * @property int $rate_percent
 * @property bool $prices_include_tax
 * @property bool $tax_shipping
 * @property string|null $provider_name
 * @property array<string, mixed>|null $provider_config
 */
class TaxSettings extends Model
{
    /** @use HasFactory<\Database\Factories\TaxSettingsFactory> */
    use HasFactory;

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'mode',
        'rate_percent',
        'prices_include_tax',
        'tax_shipping',
        'provider_name',
        'provider_config',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => TaxMode::class,
            'rate_percent' => 'integer',
            'prices_include_tax' => 'boolean',
            'tax_shipping' => 'boolean',
            'provider_config' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
