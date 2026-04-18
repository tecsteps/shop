<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    public function delete(int $id): void
    {
        Page::query()->whereKey($id)->delete();
    }

    public function render()
    {
        $pages = Page::query()->orderByDesc('id')->paginate(20);

        return view('livewire.admin.pages.index', compact('pages'));
    }
}
