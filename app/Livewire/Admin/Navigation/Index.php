<?php

namespace App\Livewire\Admin\Navigation;

use App\Models\NavigationMenu;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    public function render(): View
    {
        $menus = NavigationMenu::query()->with('items')->get();

        return view('livewire.admin.navigation.index', [
            'menus' => $menus,
        ]);
    }
}
