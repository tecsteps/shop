<?php

namespace App\Livewire\Storefront\Collections;

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
        return view('livewire.storefront.collections.show')
            ->layout('layouts.storefront.app', [
                'title' => 'Collection',
            ]);
    }
}
