<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Theme;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public function render(): View
    {
        return view('livewire.admin.themes.index', [
            'themes' => Theme::query()->latest()->get(),
        ]);
    }
}
