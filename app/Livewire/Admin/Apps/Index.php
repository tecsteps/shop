<?php

namespace App\Livewire\Admin\Apps;

use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public function render(): View
    {
        return view('livewire.admin.apps.index', [
            'apps' => [
                ['id' => 'reviews', 'name' => 'Product Reviews', 'status' => 'available'],
                ['id' => 'email-automation', 'name' => 'Email Automation', 'status' => 'available'],
                ['id' => 'warehouse-sync', 'name' => 'Warehouse Sync', 'status' => 'available'],
            ],
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Apps',
        ]);
    }
}
