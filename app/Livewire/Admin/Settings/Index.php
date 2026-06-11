<?php

namespace App\Livewire\Admin\Settings;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Settings landing page with tabs (spec 02): General, Domains, Shipping,
 * Taxes, Checkout, Notifications. Shipping and Taxes are dedicated routes;
 * the remaining tabs render embedded child components.
 */
#[Layout('layouts::admin')]
class Index extends Component
{
    use AuthorizesRequests;

    #[Url]
    public string $tab = 'general';

    public function mount(): void
    {
        $this->authorize('viewSettings', app('current_store'));

        if (! in_array($this->tab, ['general', 'domains', 'checkout', 'notifications'], true)) {
            $this->tab = 'general';
        }
    }

    public function render(): View
    {
        return view('livewire.admin.settings.index')->title(__('Settings'));
    }
}
