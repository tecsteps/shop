<?php

namespace App\Livewire\Storefront\Products;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public string $handle;

    public int $quantity = 1;

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function addToCart(): void
    {
        // Will be implemented in Phase 4
    }

    public function incrementQuantity(): void
    {
        $this->quantity++;
    }

    public function decrementQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function render(): mixed
    {
        return view('livewire.storefront.products.show', [
            'handle' => $this->handle,
        ]);
    }
}
