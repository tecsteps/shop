<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.admin.pages.index', [
            'pages' => Page::query()
                ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
                ->latest('updated_at')
                ->paginate(10),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Pages',
        ]);
    }
}
