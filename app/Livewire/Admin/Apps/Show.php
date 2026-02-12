<?php

namespace App\Livewire\Admin\Apps;

use App\Models\AppInstallation;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('App Details')]
class Show extends Component
{
    public AppInstallation $installation;

    public function mount(AppInstallation $installation): void
    {
        $store = app('current_store');
        abort_unless($installation->store_id === $store->id, 404);

        $this->installation = $installation;
    }

    public function uninstall(): void
    {
        $this->installation->delete();

        $this->dispatch('toast', type: 'success', message: 'App uninstalled.');

        $this->redirect(route('admin.apps.index'), navigate: true);
    }

    public function render(): View
    {
        $this->installation->load('app');

        return view('livewire.admin.apps.show');
    }
}
