<?php

namespace App\Livewire\Admin\Apps;

use App\Models\AppInstallation;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Show extends Component
{
    public AppInstallation $installation;

    public function mount(AppInstallation $installation): void
    {
        $this->installation = $installation->load(['app', 'webhookSubscriptions']);
    }

    public function uninstall(): void
    {
        $this->installation->update(['status' => 'uninstalled']);

        $this->dispatch('toast', type: 'success', message: __('App uninstalled successfully.'));

        $this->redirectRoute('admin.apps.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.apps.show');
    }
}
