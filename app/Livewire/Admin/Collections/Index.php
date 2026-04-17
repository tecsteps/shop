<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Illuminate\View\View;
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

    public function mount(): void
    {
        $this->authorize('viewAny', Collection::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('viewAny', Collection::class);

        $query = Collection::query()->withCount('products');

        if (trim($this->search) !== '') {
            $query->where('title', 'like', '%'.trim($this->search).'%');
        }

        return view('livewire.admin.collections.index', [
            'collections' => $query->orderByDesc('updated_at')->paginate(20),
        ]);
    }
}
