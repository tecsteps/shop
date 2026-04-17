<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Collection as CollectionModel;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Index extends Component
{
    use EnsuresStore;

    public function mount(): void
    {
        $this->ensureCurrentStore();
    }

    public function render(): View
    {
        $collections = CollectionModel::query()
            ->where('status', CollectionStatus::Active->value)
            ->withCount('products')
            ->orderBy('title')
            ->get();

        return view('livewire.storefront.collections.index', [
            'collections' => $collections,
        ]);
    }
}
