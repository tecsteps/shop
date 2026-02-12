<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\View\View;
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

    #[Url]
    public string $statusFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $store = app('current_store');

        Page::query()
            ->where('store_id', $store->id)
            ->where('id', $id)
            ->delete();

        $this->dispatch('toast', type: 'success', message: 'Page deleted.');
    }

    public function render(): View
    {
        $store = app('current_store');

        $pages = Page::query()
            ->where('store_id', $store->id)
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.pages.index', [
            'pages' => $pages,
            'statuses' => PageStatus::cases(),
        ]);
    }
}
