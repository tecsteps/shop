<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Page;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Index extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Page::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->authorize('delete', Page::class);

        $page = Page::findOrFail($id);
        $page->delete();

        session()->flash('toast', ['type' => 'success', 'message' => __('Page deleted.')]);
    }

    public function render(): View
    {
        $query = Page::query();

        if ($this->search !== '') {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        return view('livewire.admin.pages.index', [
            'pages' => $query->latest()->paginate(15),
        ]);
    }
}
