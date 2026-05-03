<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Models\Discount;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function disable(int $discountId): void
    {
        Discount::query()
            ->whereKey($discountId)
            ->firstOrFail()
            ->forceFill(['status' => DiscountStatus::Disabled])
            ->save();

        session()->flash('admin_toast', ['message' => 'Discount disabled.', 'type' => 'success']);
    }

    public function render(): View
    {
        return view('livewire.admin.discounts.index', [
            'discounts' => Discount::query()
                ->when($this->search !== '', fn ($query) => $query->where('code', 'like', '%'.$this->search.'%'))
                ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
                ->latest('updated_at')
                ->paginate(10),
            'statuses' => DiscountStatus::cases(),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Discounts',
        ]);
    }
}
