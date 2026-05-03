<?php

namespace App\Models;

use App\Enums\TaxMode;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSettings extends Model
{
    /** @use HasFactory<\Database\Factories\TaxSettingsFactory> */
    use BelongsToStore, HasFactory;

    public const CREATED_AT = null;

    protected $primaryKey = 'store_id';

    public $incrementing = false;

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

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'mode' => TaxMode::Manual->value,
        'provider' => 'none',
        'prices_include_tax' => false,
        'config_json' => '{}',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => TaxMode::class,
            'prices_include_tax' => 'bool',
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
