<?php

namespace App\Livewire\Admin\Settings\Webhooks;

use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Deliveries extends Component
{
    public int $subscriptionId;

    public function mount(int $subscription): void
    {
        $this->subscriptionId = $subscription;
    }

    public function render(): View
    {
        $subscription = WebhookSubscription::query()->findOrFail($this->subscriptionId);
        $deliveries = WebhookDelivery::query()
            ->where('subscription_id', $subscription->getKey())
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('livewire.admin.settings.webhooks.deliveries', [
            'subscription' => $subscription,
            'deliveries' => $deliveries,
        ]);
    }
}
