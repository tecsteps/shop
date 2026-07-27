<?php

namespace App\Livewire\Admin\Apps;

use App\Models\AppInstallation;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

/**
 * App installation detail: scopes, webhook subscriptions, and recent
 * deliveries (spec 03 §15).
 */
class Show extends Component
{
    public AppInstallation $installation;

    public function mount(AppInstallation $installation): void
    {
        Gate::authorize('manage-apps');

        $this->installation = $installation->load('app');
    }

    /**
     * Uninstall the app and return to the apps list.
     */
    public function uninstallApp(): void
    {
        Gate::authorize('manage-apps');

        $this->installation->forceFill(['status' => 'uninstalled'])->save();

        $this->dispatch('toast', type: 'success', message: 'App uninstalled');

        $this->redirectRoute('admin.apps.index', navigate: true);
    }

    public function render(): View
    {
        $webhooks = $this->installation->webhookSubscriptions()->orderByDesc('id')->get();

        $deliveries = WebhookDelivery::query()
            ->whereIn('subscription_id', $webhooks->pluck('id'))
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('livewire.admin.apps.show', [
            'webhooks' => $webhooks,
            'deliveries' => $deliveries,
        ])->layout('admin.layouts.app')->title($this->installation->app->name);
    }
}
