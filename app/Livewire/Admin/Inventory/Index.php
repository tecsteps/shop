<?php

namespace App\Livewire\Admin\Inventory;

use Livewire\Component;

class Index extends Component
{
    public function render(): mixed
    {
        return view('livewire.admin.inventory.index')
            ->layout('layouts.admin', ['breadcrumbs' => [['label' => 'Inventory']]]);
    }
}
