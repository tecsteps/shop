<?php

namespace App\Livewire\Storefront\Products;

use Livewire\Component;

class Show extends Component
{
    public string $handle = '';

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.products.show')
            ->layout('layouts.storefront.app', [
                'title' => 'Product',
            ]);
    }
}
