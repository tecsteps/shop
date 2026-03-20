<?php

namespace App\Livewire\Admin\Navigation;

use App\Models\NavigationMenu;
use Livewire\Component;

class Index extends Component
{
    public function render(): mixed
    {
        return view('livewire.admin.navigation.index', [
            'menus' => NavigationMenu::query()->with('items')->get(),
        ])->layout('layouts.admin.app', ['title' => 'Navigation']);
    }
}
