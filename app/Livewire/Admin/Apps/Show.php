<?php

namespace App\Livewire\Admin\Apps;

use App\Models\AppInstallation;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Show extends Component
{
    public AppInstallation $installation;

    public function mount(AppInstallation $installation): void
    {
        $installation->loadMissing('app', 'subscriptions');
        $this->installation = $installation;
    }

    public function render()
    {
        return view('livewire.admin.apps.show');
    }
}
