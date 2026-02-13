<?php

namespace App\Models;

use App\Enums\TaxMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property TaxMode $mode
 * @property bool $prices_include_tax
 * @property array<string, mixed>|null $config_json
 */
class TaxSettings extends Model
{
    /** @use HasFactory<\Database\Factories\TaxSettingsFactory> */
    use HasFactory;

    protected $table = 'tax_settings';

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'mode',
        'provider',
        'prices_include_tax',
        'config_json',
    ];

    protected function casts(): array
    {
        return [
            'mode' => TaxMode::class,
            'prices_include_tax' => 'boolean',
            'config_json' => 'array',
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
