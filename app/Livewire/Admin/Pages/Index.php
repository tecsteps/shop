<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $confirmingDelete = false;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Page::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Open the delete confirmation modal for a page.
     */
    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    /**
     * Delete the page (spec 03 §13.1).
     */
    public function delete(): void
    {
        $this->confirmingDelete = false;

        $page = Page::query()->find($this->deletingId);
        $this->deletingId = null;

        if ($page === null) {
            return;
        }

        $this->authorize('delete', $page);

        $page->delete();

        $this->dispatch('toast', type: 'success', message: 'Page deleted');
    }

    public function render(): View
    {
        $pages = Page::query()
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.addcslashes($this->search, '\\%_').'%';

                $query->where('title', 'like', $term);
            })
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('livewire.admin.pages.index', [
            'pages' => $pages,
            'hasPages' => Page::query()->exists(),
        ])->layout('admin.layouts.app')->title('Pages');
    }
}
