<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getPagesProperty()
    {
        $query = Page::withoutGlobalScopes()
            ->where('store_id', session('store_id'));

        if ($this->search) {
            $query->where('title', 'like', "%{$this->search}%");
        }

        return $query->orderByDesc('updated_at')->paginate(20);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.pages.index', [
            'pages' => $this->pages,
        ]);
    }
}
