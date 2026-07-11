<?php

namespace App\Livewire\Admin\Pages;

use App\Livewire\Admin\AdminComponent;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Index extends AdminComponent
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public function mount(): void
    {
        Gate::authorize('viewAny', Page::class);
    }

    public function deletePage(int $id): void
    {
        $page = Page::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($id);
        Gate::authorize('delete', $page);
        $page->delete();
        $this->toast('Page deleted.');
    }

    #[Computed]
    public function pages()
    {
        return Page::query()->where('store_id', $this->currentStore()->getKey())->when($this->search, fn (Builder $query) => $query->where('title', 'like', '%'.$this->search.'%'))->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))->latest('updated_at')->paginate(20);
    }

    public function render()
    {
        return view('livewire.admin.pages.index');
    }
}
