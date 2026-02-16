<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.admin.layout', ['title' => 'Pages'])]
class Index extends Component
{
    use WithPagination;

    public function render(): mixed
    {
        $store = app('current_store');

        return view('livewire.admin.pages.index', [
            'pages' => Page::query()->where('store_id', $store->id)->latest()->paginate(15),
        ]);
    }
}
