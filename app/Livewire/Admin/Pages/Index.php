<?php

namespace App\Livewire\Admin\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Locked]
    public int $storeId;

    public string $search = '';

    public function mount(): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->authorize('viewAny', Page::class);

        $this->storeId = $store->getKey();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function pages(): LengthAwarePaginator
    {
        return Page::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'like', $search)
                        ->orWhere('handle', 'like', $search);
                });
            })
            ->latest('updated_at')
            ->latest('id')
            ->paginate(20);
    }

    public function statusColor(PageStatus $status): string
    {
        return match ($status) {
            PageStatus::Published => 'green',
            PageStatus::Archived => 'red',
            default => 'zinc',
        };
    }

    public function render(): mixed
    {
        return view('livewire.admin.pages.index', [
            'pages' => $this->pages(),
        ])->layout('layouts.app', [
            'title' => __('Pages'),
        ]);
    }
}
