<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.admin.layout', ['title' => 'Collections'])]
class Index extends Component
{
    use WithPagination;

    public function render(): mixed
    {
        $store = app('current_store');

        return view('livewire.admin.collections.index', [
            'collections' => Collection::query()
                ->where('store_id', $store->id)
                ->withCount('products')
                ->latest()
                ->paginate(15),
        ]);
    }
}
