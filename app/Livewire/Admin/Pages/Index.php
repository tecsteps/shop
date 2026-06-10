<?php

namespace App\Livewire\Admin\Pages;

use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Page;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
class Index extends Component
{
    use AuthorizesRequests, SendsToasts, WithPagination;

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

    /**
     * @return LengthAwarePaginator<int, Page>
     */
    #[Computed]
    public function pages(): LengthAwarePaginator
    {
        return Page::query()
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->orderByDesc('updated_at')
            ->paginate(15);
    }

    #[Computed]
    public function hasAnyPages(): bool
    {
        return Page::query()->exists();
    }

    public function render(): View
    {
        return view('livewire.admin.pages.index')->title(__('Pages'));
    }
}
