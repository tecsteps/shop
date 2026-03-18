<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deletePage(int $id): void
    {
        Page::where('store_id', app('current_store')->id)->findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: __('Page deleted.'));
    }

    #[Computed]
    public function pages(): LengthAwarePaginator
    {
        $store = app('current_store');

        return Page::query()
            ->where('store_id', $store->id)
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.admin.pages.index');
    }
}
