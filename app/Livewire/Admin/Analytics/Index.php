<?php

namespace App\Livewire\Admin\Analytics;

use App\Models\Store;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('viewAnalytics', Store::class);
    }

    public function render(): View
    {
        return view('livewire.admin.analytics.index');
    }
}
