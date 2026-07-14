<?php

namespace App\Livewire\Admin\Pages;

use App\Livewire\Admin\AdminComponent;
use App\Models\Page;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class Index extends AdminComponent
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorizeAction('viewAny', Page::class);
    }

    public function updatedSearch(): void
    {
        $this->authorizeAction('viewAny', Page::class);
        $this->resetPage();
    }

    #[Computed]
    public function pages(): mixed
    {
        return Page::withoutGlobalScopes()
            ->where('store_id', $this->currentStore()->id)
            ->when(trim($this->search) !== '', fn ($query) => $query->where('title', 'like', '%'.trim($this->search).'%'))
            ->latest('updated_at')
            ->paginate(20);
    }

    public function render(): View
    {
        return $this->admin(view('admin.pages.index'), 'Pages', [['label' => 'Pages']]);
    }
}
