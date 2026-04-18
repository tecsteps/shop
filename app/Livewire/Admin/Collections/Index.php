<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function render()
    {
        $query = Collection::query()
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->orderByDesc('id');

        return view('livewire.admin.collections.index', [
            'collections' => $query->paginate(20),
        ]);
    }
}
