<?php

namespace App\Livewire\Admin\Navigation;

use App\Models\NavigationMenu;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public function render(): View
    {
        return view('livewire.admin.navigation.index', [
            'menus' => NavigationMenu::query()->with('items')->get(),
        ]);
    }
}
