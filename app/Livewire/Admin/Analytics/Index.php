<?php

namespace App\Livewire\Admin\Analytics;

use Livewire\Component;

class Index extends Component
{
    public function render(): mixed
    {
        return view('livewire.admin.analytics.index')
            ->layout('layouts.admin', ['breadcrumbs' => [['label' => 'Analytics']]]);
    }
}
