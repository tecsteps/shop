<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Pages')]
class Index extends Component
{
    use WithPagination;

    #[Url]
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
        $store = app('current_store');

        return Page::query()
            ->where('store_id', $store->id)
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->latest('updated_at')
            ->paginate(15);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.pages.index');
    }
}
