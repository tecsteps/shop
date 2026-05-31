<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection as ProductCollection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Lists all active collections for the current store in a card grid.
 */
#[Layout('storefront.layouts.app')]
#[Title('Collections')]
class Index extends Component
{
    public function render()
    {
        $collections = ProductCollection::query()
            ->published()
            ->orderBy('title')
            ->get();

        return view('livewire.storefront.collections.index', [
            'collections' => $collections,
        ]);
    }
}
