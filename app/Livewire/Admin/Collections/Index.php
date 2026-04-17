<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): mixed
    {
        $collections = Collection::query()
            ->withCount('products')
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.collections.index', [
            'collections' => $collections,
        ])->layout('layouts.admin.app', ['title' => 'Collections']);
    }
}
