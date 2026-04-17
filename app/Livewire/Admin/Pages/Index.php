<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
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

    /**
     * @return LengthAwarePaginator<Page>
     */
    #[Computed]
    public function pages(): LengthAwarePaginator
    {
        return Page::query()
            ->when($this->search, function ($query) {
                $query->where('title', 'like', "%{$this->search}%");
            })
            ->orderByDesc('updated_at')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.pages.index')
            ->layout('layouts.admin', ['title' => 'Pages']);
    }
}
