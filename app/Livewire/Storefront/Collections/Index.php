<?php

namespace App\Livewire\Storefront\Collections;

use App\Enums\CollectionStatus;
use App\Models\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Index extends Component
{
    public function render(): mixed
    {
        $collections = Collection::query()
            ->where('status', CollectionStatus::Active)
            ->withCount('products')
            ->orderBy('title')
            ->get();

        return view('livewire.storefront.collections.index', [
            'collections' => $collections,
        ]);
    }
}
