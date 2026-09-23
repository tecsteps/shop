<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function render(): mixed
    {
        Gate::authorize('view-collections');
        $canManage = Gate::allows('manage-products');

        return view('livewire.admin.collections.index', [
            'canManage' => $canManage,
            'collections' => Collection::query()->withCount('products')->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.addcslashes($this->search, '%_\\').'%'))->orderBy('title')->paginate(15),
        ])->layout('layouts.admin');
    }
}
