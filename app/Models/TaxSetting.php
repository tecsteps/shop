<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxSetting extends Model
{
    use HasFactory;

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['store_id', 'mode', 'provider', 'prices_include_tax', 'config_json', 'default_rate', 'rates_json'];

    protected function casts(): array
    {
        return [
            'prices_include_tax' => 'boolean',
            'config_json' => 'array',
            'rates_json' => 'array',
        ];
    }

    /** @return array<string, mixed> */
    public function taxRatesForCalculator(): array
    {
        $taxRates = $this->config_json['tax_rates'] ?? null;

        if (is_array($taxRates) && array_is_list($taxRates)) {
            $rates = [];

            foreach ($taxRates as $taxRate) {
                if (! is_array($taxRate) || ! isset($taxRate['country_code'], $taxRate['rate'])) {
                    continue;
                }

                $countryCode = strtoupper((string) $taxRate['country_code']);
                $provinceCode = strtoupper((string) ($taxRate['province_code'] ?? ''));

                if ($provinceCode !== '') {
                    $rates[$countryCode][$provinceCode] = (int) $taxRate['rate'];
                } else {
                    $rates[$countryCode] = (int) $taxRate['rate'];
                }
            }

            if ($rates !== [] || ($this->rates_json ?? []) === []) {
                return $rates;
            }
        }

        return is_array($taxRates) ? $taxRates : ($this->rates_json ?? []);
    }

    /** @return array{default_tax_rate: int, tax_rates: list<array<string, mixed>>|array<string, mixed>, fallback: string} */
    public function taxConfiguration(): array
    {
        $configuration = $this->config_json ?? [];
        $rates = $configuration['tax_rates'] ?? null;

        if (! is_array($rates) || $rates === []) {
            $rates = collect($this->rates_json ?? [])->map(static fn (mixed $rate, string $countryCode): array => [
                'country_code' => strtoupper($countryCode),
                'rate' => (int) $rate,
            ])->values()->all();
        }

        return [
            'default_tax_rate' => (int) ($configuration['default_tax_rate'] ?? $this->default_rate ?? 0),
            'tax_rates' => $rates,
            'fallback' => $configuration['fallback'] ?? 'allow',
        ];
    }

    public function defaultTaxRateForCalculator(): int
    {
        return (int) ($this->config_json['default_tax_rate'] ?? $this->default_rate ?? 0);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
