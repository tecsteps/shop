<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
#[Title('Discounts')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public function render(): View
    {
        Gate::authorize('viewAny', Discount::class);
        $discounts = Discount::query()->when($this->search, fn ($query) => $query->where('code', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))->latest()->paginate(20);

        return view('livewire.admin.discounts.index', compact('discounts'));
    }
}
