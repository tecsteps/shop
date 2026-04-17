<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Page::class);
    }

    public function render(): View
    {
        $this->authorize('viewAny', Page::class);

        return view('livewire.admin.pages.index', [
            'pages' => Page::query()->orderByDesc('updated_at')->paginate(20),
        ]);
    }
}
