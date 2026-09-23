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
        Gate::authorize('manage-store-settings');
        Page::query()->findOrFail($pageId)->delete();
        session()->flash('status', 'Page deleted.');
    }

    public function render(): mixed
    {
        Gate::authorize('manage-store-settings');

        return view('livewire.admin.pages.index', ['pages' => Page::query()->orderBy('title')->paginate(15)])->layout('layouts.admin');
    }
}
