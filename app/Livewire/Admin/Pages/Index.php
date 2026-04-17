<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public function render()
    {
        $pages = Page::query()->orderByDesc('updated_at')->get();

        return view('livewire.admin.pages.index', compact('pages'))->title('Pages');
    }
}
