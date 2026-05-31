<?php

namespace App\Livewire\Admin\Apps;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\AppInstallation;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Installed app detail: granted scopes, webhook subscriptions, and basic usage.
 */
#[Layout('livewire.admin.layout.app')]
class Show extends Component
{
    use BindsCurrentStore;

    public AppInstallation $installation;

    public function mount(int $appId): void
    {
        if (! Gate::allows('manage-apps')) {
            abort(403);
        }

        $this->installation = AppInstallation::query()
            ->with(['app', 'webhookSubscriptions'])
            ->where('store_id', app('current_store')->id)
            ->findOrFail($appId);
    }

    public function uninstall(): mixed
    {
        if (! Gate::allows('manage-apps')) {
            abort(403);
        }

        $this->installation->update(['status' => 'uninstalled']);
        $this->dispatch('toast', type: 'success', message: __('App uninstalled'));

        return $this->redirectRoute('admin.apps.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.apps.show');
    }
}
