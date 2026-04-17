<?php

namespace App\Livewire\Storefront;

use App\Models\NavigationMenu;
use Illuminate\View\View;
use Livewire\Component;

class Navigation extends Component
{
    public string $handle = 'main-menu';

    public function mount(string $handle = 'main-menu'): void
    {
        $this->handle = $handle;
    }

    public function render(): View
    {
        $menu = NavigationMenu::query()
            ->where('handle', $this->handle)
            ->with('items')
            ->first();

        return view('livewire.storefront.navigation', [
            'menu' => $menu,
        ]);
    }
}
