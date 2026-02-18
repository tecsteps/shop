<?php

namespace App\Livewire\Storefront;

use App\Services\ThemeSettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Home extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $themeSettings = [];

    public function mount(): void
    {
        $this->themeSettings = app(ThemeSettingsService::class)->get();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.storefront.home');
    }
}
