<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Store;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|size:3')]
    public string $defaultCurrency = 'EUR';

    #[Validate('required|string|max:10')]
    public string $defaultLocale = 'en';

    #[Validate('required|string|max:64')]
    public string $timezone = 'UTC';

    public function mount(): void
    {
        /** @var Store $store */
        $store = app('current_store');

        $this->name = (string) $store->name;
        $this->defaultCurrency = (string) $store->default_currency;
        $this->defaultLocale = (string) $store->default_locale;
        $this->timezone = (string) $store->timezone;
    }

    public function save(): void
    {
        $this->validate();

        /** @var Store $store */
        $store = app('current_store');

        $store->update([
            'name' => $this->name,
            'default_currency' => strtoupper($this->defaultCurrency),
            'default_locale' => $this->defaultLocale,
            'timezone' => $this->timezone,
        ]);

        session()->flash('status', 'Settings saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.index');
    }
}
