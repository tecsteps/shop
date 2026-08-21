<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function delete(int $pageId): void
    {
        $page = Page::query()->findOrFail($pageId);
        $this->authorize('delete', $page);
        $page->delete();
        $this->dispatch('toast', message: 'Page deleted.');
    }

    public function render(): View
    {
        $pages = Page::query()
            ->when($this->search !== '', fn ($query) => $query->where(function ($nested): void {
                $nested->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('handle', 'like', '%'.$this->search.'%');
            }))
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->latest('updated_at')
            ->paginate(20);

        return view('livewire.admin.pages.index', compact('pages'))->layout('layouts.admin');
    }
}
