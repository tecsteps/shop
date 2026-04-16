<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Index extends Component
{
    public function render()
    {
        $collections = Collection::query()
            ->where('status', CollectionStatus::Active->value)
            ->withCount('products')
            ->get();

        return view('livewire.storefront.collections.index', compact('collections'))
            ->title('Collections');
    }
}
