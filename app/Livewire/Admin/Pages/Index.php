<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function delete(int $pageId): void
    {
        $page = Page::query()->findOrFail($pageId);
        Gate::authorize('delete', $page);
        $page->delete();
        session()->flash('status', 'Page deleted.');
    }

    public function render(): mixed
    {
        Gate::authorize('viewAny', Page::class);

        return view('livewire.admin.pages.index', ['pages' => Page::query()->orderBy('title')->paginate(15)])->layout('layouts.admin');
    }
}
