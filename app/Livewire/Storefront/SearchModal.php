<?php

namespace App\Livewire\Storefront;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SearchModal extends Component
{
    public bool $open = false;

    public string $query = '';

    public function render(): View
    {
        $suggestions = $this->query === '' ? collect() : Product::published()
            ->where('title', 'like', '%'.$this->query.'%')->limit(6)->get();

        return view('livewire.storefront.search-modal', ['suggestions' => $suggestions]);
    }
}
