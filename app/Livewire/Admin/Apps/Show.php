<?php

namespace App\Livewire\Admin\Apps;

use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\AppInstallation;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    use ResolvesAdminStore;

    public AppInstallation $installation;

    public function mount(AppInstallation $installation): void
    {
        abort_if($installation->store_id !== $this->store()->getKey(), 404);

        Gate::authorize('manage-apps');

        $this->installation = $installation;
    }

    public function render()
    {
        $installation = AppInstallation::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($this->installation->getKey())
            ->with(['app', 'webhookSubscriptions', 'oauthTokens'])
            ->firstOrFail();

        $subscriptionIds = $installation->webhookSubscriptions->pluck('id');

        $deliveryStats = $subscriptionIds->isEmpty()
            ? ['count' => 0, 'last_at' => null]
            : [
                'count' => WebhookDelivery::query()->whereIn('subscription_id', $subscriptionIds)->count(),
                'last_at' => WebhookDelivery::query()->whereIn('subscription_id', $subscriptionIds)->max('last_attempt_at'),
            ];

        return view('livewire.admin.apps.show', [
            'installation' => $installation,
            'deliveryStats' => $deliveryStats,
        ])->layout('livewire.admin.layout.app', ['title' => $installation->app?->name ?? 'App']);
    }
}
