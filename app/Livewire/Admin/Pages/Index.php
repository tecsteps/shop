<?php

namespace App\Livewire\Admin\Pages;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use DispatchesToasts, WithPagination;

    #[Layout('layouts.admin.app')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Page::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function pages(): LengthAwarePaginator
    {
        $query = Page::query();

        if (trim($this->search) !== '') {
            $query->where('title', 'like', '%'.trim($this->search).'%');
        }

        return $query->latest('updated_at')->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.pages.index');
    }
}
