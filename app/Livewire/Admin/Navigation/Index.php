<?php

namespace App\Livewire\Admin\Navigation;

use Livewire\Component;

class Index extends Component
{
    public function render(): mixed
    {
        return view('livewire.admin.navigation.index')
            ->layout('layouts.admin', ['breadcrumbs' => [['label' => 'Navigation']]]);
    }
}
