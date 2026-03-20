<?php

namespace App\Livewire\Storefront\Collections;

use Livewire\Component;

class Index extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.collections.index')
            ->layout('layouts.storefront.app', [
                'title' => 'Collections',
            ]);
    }
}
