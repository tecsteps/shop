<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render(): mixed
    {
        return view('livewire.admin.pages-admin.index', [
            'pages' => Page::query()->latest()->paginate(20),
        ])->layout('layouts.admin.app', ['title' => 'Pages']);
    }
}
