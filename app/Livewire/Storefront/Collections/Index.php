<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection;
use Livewire\Component;

class Index extends Component
{
    public function render(): mixed
    {
        return view('storefront.collections', [
            'collections' => Collection::query()->where('status', 'active')->withCount(['products' => fn ($query) => $query->where('status', 'active')->whereNotNull('published_at')])->orderBy('title')->paginate(12),
        ])->layout('storefront.layouts.app');
    }
}
