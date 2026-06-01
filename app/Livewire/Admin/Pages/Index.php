<?php

namespace App\Livewire\Admin\Pages;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Content pages list: searchable, paginated.
 */
#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use BindsCurrentStore;
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Page::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getPagesProperty()
    {
        return Page::query()
            ->when($this->search !== '', fn (Builder $q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->orderByDesc('updated_at')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.pages.index');
    }
}
