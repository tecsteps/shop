<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::storefront')]
class Index extends Component
{
    public function render(): View
    {
        $collections = Collection::query()
            ->published()
            ->with(['products' => fn ($query) => $query->published()->with('media')->limit(1)])
            ->withCount('products')
            ->orderBy('title')
            ->get();

        return view('livewire.storefront.collections.index', [
            'collections' => $collections,
        ])->title(__('Collections'));
    }
}
