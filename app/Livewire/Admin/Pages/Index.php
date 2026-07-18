<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
#[Title('Pages')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function render(): View
    {
        Gate::authorize('viewAny', Page::class);
        $pages = Page::query()->when($this->search, fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))->latest('updated_at')->paginate(20);

        return view('livewire.admin.pages.index', compact('pages'));
    }
}
