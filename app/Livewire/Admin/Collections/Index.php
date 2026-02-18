<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        /** @var Store $store */
        $store = app('current_store');

        $query = Collection::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->withCount('products');

        if ($this->search) {
            $query->where('title', 'like', "%{$this->search}%");
        }

        $collections = $query->latest('updated_at')->paginate(15);

        return view('livewire.admin.collections.index', [
            'collections' => $collections,
        ]);
    }
}
