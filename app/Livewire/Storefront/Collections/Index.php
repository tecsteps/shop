<?php

namespace App\Livewire\Storefront\Collections;

use App\Models\Collection as ProductCollection;
use Livewire\Component;

class Index extends Component
{
    public function render(): mixed
    {
        return view('livewire.storefront.collections.index', ['collections' => ProductCollection::query()->where('status', 'active')->latest()->get()])->layout('layouts.storefront');
    }
}
