<?php

namespace App\Livewire\Storefront\Products;

use App\Models\Product;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public string $handle = '';

    public ?Product $product = null;

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        $this->product = Product::query()
            ->where('handle', $handle)
            ->first();
    }

    public function render(): View
    {
        return view('livewire.storefront.products.show');
    }
}
