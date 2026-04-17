<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
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

    public function render(): View
    {
        $collections = Collection::query()
            ->withCount('products')
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.collections.index', [
            'collections' => $collections,
        ]);
    }
}
