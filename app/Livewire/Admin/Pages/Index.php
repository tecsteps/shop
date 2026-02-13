<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use WithPagination;

    public function render(): View
    {
        $pages = Page::query()->latest()->paginate(15);

        return view('livewire.admin.pages.index', [
            'pages' => $pages,
        ]);
    }
}
