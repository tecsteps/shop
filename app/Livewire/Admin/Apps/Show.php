<?php

namespace App\Livewire\Admin\Apps;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\AppInstallation;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Show extends Component
{
    use DispatchesToasts;

    #[Layout('layouts.admin.app')]
    public AppInstallation $installation;

    public function mount(AppInstallation $installation): void
    {
        $this->authorize('viewSettings', app('current_store'));

        $this->installation = $installation->load('app');
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function scopes(): array
    {
        return $this->installation->scopes_json ?? [];
    }

    #[Computed]
    public function webhooks(): \Illuminate\Support\Collection
    {
        return WebhookSubscription::query()
            ->where('app_installation_id', $this->installation->id)
            ->get();
    }

    #[Computed]
    public function deliveryCount(): int
    {
        $subscriptionIds = $this->webhooks->pluck('id');

        if ($subscriptionIds->isEmpty()) {
            return 0;
        }

        return WebhookDelivery::whereIn('subscription_id', $subscriptionIds)->count();
    }

    public function render()
    {
        return view('livewire.admin.apps.show');
    }
}
