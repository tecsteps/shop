<?php

namespace App\Livewire\Admin\Apps;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\AppInstallation;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    use DispatchesToasts;

    #[Layout('layouts.admin.app')]
    public ?int $uninstallId = null;

    public bool $confirmingUninstall = false;

    public function mount(): void
    {
        $this->authorize('viewSettings', app('current_store'));
    }

    #[Computed]
    public function installedApps(): Collection
    {
        return app('current_store')->appInstallations()
            ->with('app')
            ->latest('installed_at')
            ->get();
    }

    public function confirmUninstall(int $installationId): void
    {
        $this->uninstallId = $installationId;
        $this->confirmingUninstall = true;
    }

    public function uninstallApp(): void
    {
        $this->authorize('viewSettings', app('current_store'));

        $installation = AppInstallation::find($this->uninstallId);

        if ($installation) {
            $installation->delete();
            $this->toast('App uninstalled');
        }

        $this->confirmingUninstall = false;
        $this->uninstallId = null;
    }

    public function render()
    {
        return view('livewire.admin.apps.index');
    }
}
