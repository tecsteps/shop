<?php

namespace App\Livewire\Storefront;

use App\Services\ThemeSettingsService;
use Livewire\Component;

class Home extends Component
{
    public function render(): \Illuminate\View\View
    {
        $themeSettings = app(ThemeSettingsService::class);

        return view('livewire.storefront.home', [
            'settings' => $themeSettings->all(),
        ])->layout('layouts.storefront.app', [
            'title' => 'Home',
        ]);
    }
}
