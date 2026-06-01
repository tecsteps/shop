<?php

namespace App\Models;

use App\Enums\TaxMode;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxSettings extends Model
{
    use BelongsToStore;

    /** @use HasFactory<\Database\Factories\TaxSettingsFactory> */
    use HasFactory;

    /**
     * The primary key is the owning store_id (one row per store).
     *
     * @var string
     */
    protected $primaryKey = 'store_id';

    /**
     * The primary key is a foreign key, not an auto-incrementing integer.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The tax_settings table has no timestamps.
     *
     * @var bool
     */
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

    /**
     * The default tax rate in basis points (e.g. 1900 = 19%) from config, or 0.
     */
    public function defaultRateBasisPoints(): int
    {
        return (int) ($this->config_json['default_rate'] ?? 0);
    }

    /**
     * The display name for the configured tax line.
     */
    public function taxName(): string
    {
        return (string) ($this->config_json['name'] ?? 'Tax');
    }
}
