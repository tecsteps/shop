<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Discount::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('viewAny', Discount::class);

        $query = Discount::query();

        if (trim($this->search) !== '') {
            $query->where('code', 'like', '%'.trim($this->search).'%');
        }

        return view('livewire.admin.discounts.index', [
            'discounts' => $query->orderByDesc('created_at')->paginate(20),
        ]);
    }
}
