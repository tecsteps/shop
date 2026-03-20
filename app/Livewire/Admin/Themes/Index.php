<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Theme;
use Livewire\Component;

class Index extends Component
{
    public function render(): mixed
    {
        return view('livewire.admin.themes.index', [
            'themes' => Theme::query()->latest()->get(),
        ])->layout('layouts.admin.app', ['title' => 'Themes']);
    }
}
