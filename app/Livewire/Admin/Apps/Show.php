<?php

namespace App\Livewire\Admin\Apps;

use App\Enums\AppInstallationStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\AppInstallation;
use App\Models\Store;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Installed app detail (spec 03 section 15): granted scopes, webhook
 * subscriptions, and usage stats. OAuth-driven API usage is deferred with
 * the OAuth flow, so the usage panel reports the token count only.
 */
#[Layout('layouts::admin')]
class Show extends Component
{
    use AuthorizesRequests, SendsToasts;

    #[Locked]
    public int $installationId;

    public function mount(int $installation): void
    {
        $this->installationId = $installation;

        $this->authorize('manageApps', $this->store());

        $this->installation();
    }

    public function uninstallApp(): void
    {
        $this->authorize('manageApps', $this->store());

        $installation = $this->installation();

        $installation->update(['status' => AppInstallationStatus::Uninstalled]);

        $installation->webhookSubscriptions()->update([
            'status' => WebhookSubscriptionStatus::Disabled,
        ]);

        $this->flashToast(__(':app uninstalled.', ['app' => $installation->app->name]));

        $this->redirectRoute('admin.apps.index', navigate: true);
    }

    #[Computed]
    public function installation(): AppInstallation
    {
        return AppInstallation::query()
            ->with(['app', 'webhookSubscriptions.latestDelivery', 'oauthTokens'])
            ->where('store_id', $this->store()->getKey())
            ->where('status', '!=', AppInstallationStatus::Uninstalled)
            ->findOrFail($this->installationId);
    }

    public function render(): View
    {
        return view('livewire.admin.apps.show')->title($this->installation->app->name);
    }

    protected function store(): Store
    {
        return app('current_store');
    }
}
