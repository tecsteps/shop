<?php

namespace App\Livewire\Admin\Settings;

use App\Models\TaxSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Taxes extends Component
{
    public string $mode = 'manual';

    public bool $pricesIncludeTax = true;

    public string $provider = 'none';

    public string $providerApiKey = '';

    public bool $hasSavedProviderKey = false;

    public string $fallback = 'allow';

    public string $defaultRatePercentage = '19.00';

    public int $defaultRate = 1900;

    /** @var list<array{zone_name: string, rate_percentage: string}> */
    public array $manualRates = [['zone_name' => 'DE', 'rate_percentage' => '19.00']];

    public function mount(): void
    {
        Gate::authorize('manage-shipping');
        $taxes = TaxSetting::query()->where('store_id', app('current_store')->id)->first();
        $this->pricesIncludeTax = $taxes?->prices_include_tax ?? true;
        $this->mode = $taxes?->mode ?? 'manual';
        $this->provider = $taxes?->provider ?? 'none';
        $this->defaultRate = $taxes?->defaultTaxRateForCalculator() ?? 1900;
        $this->defaultRatePercentage = $this->basisPointsToPercentage($this->defaultRate);
        $configuration = $taxes?->config_json ?? [];
        $this->fallback = $configuration['fallback'] ?? 'allow';
        $this->manualRates = $this->rowsFromRates($taxes?->taxRatesForCalculator() ?? []);

        if (filled($configuration['provider_api_key_encrypted'] ?? null)) {
            try {
                $this->providerApiKey = Crypt::decryptString($configuration['provider_api_key_encrypted']);
                $this->hasSavedProviderKey = true;
                $this->providerApiKey = '';
            } catch (\Throwable) {
                $this->hasSavedProviderKey = false;
            }
        }
    }

    public function addManualRate(): void
    {
        if (count($this->manualRates) >= 20) {
            $this->addError('manualRates', 'A maximum of 20 manual tax rates can be configured.');

            return;
        }

        $this->manualRates[] = ['zone_name' => '', 'rate_percentage' => '0.00'];
    }

    public function removeManualRate(int $index): void
    {
        unset($this->manualRates[$index]);
        $this->manualRates = array_values($this->manualRates);

        if ($this->manualRates === []) {
            $this->manualRates = [['zone_name' => '', 'rate_percentage' => '0.00']];
        }
    }

    public function save(): void
    {
        Gate::authorize('manage-shipping');
        $rules = [
            'mode' => ['required', 'in:manual,provider'],
            'pricesIncludeTax' => ['boolean'],
            'provider' => ['required', 'in:stripe_tax,none'],
            'providerApiKey' => ['nullable', 'string', 'max:255'],
            'fallback' => ['required', 'in:allow,block'],
            'defaultRatePercentage' => ['required', 'string', 'regex:/\A(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)\z/'],
        ];

        if ($this->mode === 'manual') {
            $rules['manualRates'] = ['required', 'array', 'min:1', 'max:20'];
            $rules['manualRates.*.zone_name'] = ['required', 'string', 'max:10', 'regex:/\A[A-Z]{2}(?:-[A-Z0-9]{1,3})?\z/i'];
            $rules['manualRates.*.rate_percentage'] = ['required', 'string', 'regex:/\A(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)\z/'];
        }

        if ($this->mode === 'provider' && $this->provider === 'stripe_tax' && ! $this->hasSavedProviderKey) {
            $rules['providerApiKey'] = ['required', 'string', 'min:8', 'max:255'];
        }

        $this->validate($rules);
        $taxes = TaxSetting::query()->where('store_id', app('current_store')->id)->first();
        $configuration = $taxes?->config_json ?? [];
        $defaultRate = $this->percentageToBasisPoints($this->defaultRatePercentage);
        $rates = $this->mode === 'manual' ? $this->ratesFromRows() : ($taxes?->taxRatesForCalculator() ?? []);
        $taxRateRows = $this->rowsForStorage($rates);
        $encryptedProviderKey = filled($this->providerApiKey)
            ? Crypt::encryptString($this->providerApiKey)
            : ($configuration['provider_api_key_encrypted'] ?? null);
        $configuration = [
            ...$configuration,
            'default_tax_rate' => $defaultRate,
            'tax_rates' => $taxRateRows,
            'fallback' => $this->fallback,
        ];

        if ($encryptedProviderKey !== null) {
            $configuration['provider_api_key_encrypted'] = $encryptedProviderKey;
        }

        TaxSetting::updateOrCreate(['store_id' => app('current_store')->id], [
            'mode' => $this->mode,
            'provider' => $this->mode === 'provider' ? $this->provider : 'none',
            'prices_include_tax' => $this->pricesIncludeTax,
            'config_json' => $configuration,
            'default_rate' => $defaultRate,
            'rates_json' => $rates,
        ]);

        $this->defaultRate = $defaultRate;
        $this->hasSavedProviderKey = $encryptedProviderKey !== null;
        $this->providerApiKey = '';
        session()->flash('status', 'Tax settings saved.');
    }

    public function render(): mixed
    {
        Gate::authorize('manage-shipping');

        return view('livewire.admin.settings.taxes')->layout('layouts.admin');
    }

    /** @param array<string, mixed> $rates
     * @return list<array{zone_name: string, rate_percentage: string}>
     */
    private function rowsFromRates(array $rates): array
    {
        $rows = [];

        foreach ($rates as $countryCode => $rate) {
            if (is_array($rate)) {
                foreach ($rate as $provinceCode => $provinceRate) {
                    $rows[] = [
                        'zone_name' => strtoupper($countryCode.'-'.$provinceCode),
                        'rate_percentage' => $this->basisPointsToPercentage((int) $provinceRate),
                    ];
                }
            } elseif (is_numeric($rate)) {
                $rows[] = [
                    'zone_name' => strtoupper((string) $countryCode),
                    'rate_percentage' => $this->basisPointsToPercentage((int) $rate),
                ];
            }
        }

        return $rows === [] ? [['zone_name' => 'DE', 'rate_percentage' => '19.00']] : $rows;
    }

    /** @return array<string, int|array<string, int>> */
    private function ratesFromRows(): array
    {
        $rates = [];

        foreach ($this->manualRates as $index => $row) {
            $zoneName = strtoupper(trim($row['zone_name']));
            $rate = $this->percentageToBasisPoints($row['rate_percentage']);
            $parts = explode('-', $zoneName, 2);
            $countryCode = $parts[0];
            $provinceCode = $parts[1] ?? null;

            if ($provinceCode === null) {
                if (array_key_exists($countryCode, $rates)) {
                    throw ValidationException::withMessages(["manualRates.{$index}.zone_name" => 'Each tax zone must be unique.']);
                }

                $rates[$countryCode] = $rate;

                continue;
            }

            if (isset($rates[$countryCode]) && ! is_array($rates[$countryCode])) {
                throw ValidationException::withMessages(["manualRates.{$index}.zone_name" => 'A country-wide rate cannot be combined with regional rates for the same country.']);
            }

            $rates[$countryCode] ??= [];

            if (array_key_exists($provinceCode, $rates[$countryCode])) {
                throw ValidationException::withMessages(["manualRates.{$index}.zone_name" => 'Each tax zone must be unique.']);
            }

            $rates[$countryCode][$provinceCode] = $rate;
        }

        return $rates;
    }

    /** @param array<string, int|array<string, int>> $rates
     * @return list<array{country_code: string, province_code?: string, rate: int}>
     */
    private function rowsForStorage(array $rates): array
    {
        $rows = [];

        foreach ($rates as $countryCode => $rate) {
            if (is_array($rate)) {
                foreach ($rate as $provinceCode => $provinceRate) {
                    $rows[] = ['country_code' => $countryCode, 'province_code' => $provinceCode, 'rate' => $provinceRate];
                }
            } else {
                $rows[] = ['country_code' => $countryCode, 'rate' => $rate];
            }
        }

        return $rows;
    }

    private function percentageToBasisPoints(string $percentage): int
    {
        if (preg_match('/\A(\d{1,3})(?:\.(\d{1,2}))?\z/', trim($percentage), $matches) !== 1) {
            throw ValidationException::withMessages(['defaultRatePercentage' => 'Enter a percentage between 0 and 100 with up to two decimal places.']);
        }

        $whole = (int) $matches[1];
        $fraction = (int) str_pad($matches[2] ?? '', 2, '0');
        $basisPoints = $whole * 100 + $fraction;

        if ($basisPoints > 10000) {
            throw ValidationException::withMessages(['defaultRatePercentage' => 'The tax rate cannot exceed 100%.']);
        }

        return $basisPoints;
    }

    private function basisPointsToPercentage(int $basisPoints): string
    {
        return intdiv($basisPoints, 100).'.'.str_pad((string) ($basisPoints % 100), 2, '0', STR_PAD_LEFT);
    }
}
