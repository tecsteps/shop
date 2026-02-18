<?php

namespace App\Livewire\Storefront\Collections;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public string $handle = '';

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.storefront.collections.show');
    }
}
