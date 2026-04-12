<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public int $perPage = 20;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $pageId): void
    {
        $page = Page::query()->findOrFail($pageId);
        $page->delete();

        session()->flash('status', 'Page deleted.');
    }

    public function render(): View
    {
        $pages = Page::query()
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->latest('updated_at')
            ->paginate($this->perPage);

        return view('livewire.admin.pages.index', [
            'pages' => $pages,
        ]);
    }
}
