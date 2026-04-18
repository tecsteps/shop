<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\TaxMode;
use App\Models\TaxSettings;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Taxes extends Component
{
    public string $mode = 'manual';

    public bool $prices_include_tax = false;

    public int $default_rate_bp = 0;

    public function mount(): void
    {
        $store = app('current_store');
        $settings = TaxSettings::query()->where('store_id', $store->id)->first();
        if ($settings) {
            $this->mode = $settings->mode?->value ?? 'manual';
            $this->prices_include_tax = (bool) $settings->prices_include_tax;
            $this->default_rate_bp = (int) ($settings->config_json['default_rate_bp'] ?? 0);
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'mode' => 'required|in:manual,provider',
            'prices_include_tax' => 'boolean',
            'default_rate_bp' => 'integer|min:0|max:10000',
        ]);

        $store = app('current_store');
        TaxSettings::query()->updateOrInsert(
            ['store_id' => $store->id],
            [
                'mode' => $data['mode'],
                'provider' => 'none',
                'prices_include_tax' => (bool) $data['prices_include_tax'],
                'config_json' => json_encode(['default_rate_bp' => (int) $data['default_rate_bp']]),
            ],
        );

        session()->flash('success', 'Tax settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.settings.taxes', [
            'modes' => TaxMode::cases(),
        ]);
    }
}
