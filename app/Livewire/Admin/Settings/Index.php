<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    use ResolvesAdminStore;

    public string $tab = 'general';

    public function mount(): void
    {
        Gate::authorize('viewSettings', $this->store());
    }

    public function render()
    {
        return view('livewire.admin.settings.index', [
            'store' => $this->store(),
        ])->layout('livewire.admin.layout.app', ['title' => 'Settings']);
    }
}
