<?php

namespace App\Livewire\Admin\Developers;

use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;
use Livewire\Component;

class Index extends Component
{
    public string $webhookEventType = '';

    public string $webhookTargetUrl = '';

    public string $webhookSecret = '';

    public bool $showCreateWebhook = false;

    public bool $showDeliveries = false;

    public ?int $viewingSubscriptionId = null;

    public ?int $editingSubscriptionId = null;

    public function showCreateForm(): void
    {
        $this->showCreateWebhook = true;
    }

    public function hideCreateForm(): void
    {
        $this->showCreateWebhook = false;
    }

    public function createWebhookSubscription(): void
    {
        $this->validate([
            'webhookEventType' => 'required|string|max:255',
            'webhookTargetUrl' => 'required|url|max:2048',
        ]);

        $store = app('current_store');

        if (! $this->webhookSecret) {
            $this->webhookSecret = Str::random(32);
        }

        WebhookSubscription::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'event_type' => $this->webhookEventType,
            'target_url' => $this->webhookTargetUrl,
            'secret' => $this->webhookSecret,
            'status' => 'active',
        ]);

        $this->reset(['webhookEventType', 'webhookTargetUrl', 'webhookSecret', 'showCreateWebhook']);
        $this->dispatch('toast', type: 'success', message: 'Webhook subscription created.');
    }

    public function deleteWebhookSubscription(int $id): void
    {
        $store = app('current_store');
        $subscription = WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($id);

        $subscription->delete();
        $this->dispatch('toast', type: 'success', message: 'Webhook subscription deleted.');
    }

    public function toggleSubscriptionStatus(int $id): void
    {
        $store = app('current_store');
        $subscription = WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->findOrFail($id);

        $newStatus = $subscription->status === 'active' ? 'paused' : 'active';

        if ($newStatus === 'active') {
            $subscription->update(['status' => 'active', 'consecutive_failures' => 0]);
        } else {
            $subscription->update(['status' => 'paused']);
        }

        $this->dispatch('toast', type: 'success', message: 'Subscription '.($newStatus === 'active' ? 'activated' : 'paused').'.');
    }

    public function viewDeliveries(int $subscriptionId): void
    {
        $this->viewingSubscriptionId = $subscriptionId;
        $this->showDeliveries = true;
    }

    public function closeDeliveries(): void
    {
        $this->showDeliveries = false;
        $this->viewingSubscriptionId = null;
    }

    public function render()
    {
        $store = app('current_store');

        $subscriptions = WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->latest()
            ->get();

        $deliveries = [];
        if ($this->viewingSubscriptionId) {
            $deliveries = WebhookDelivery::query()
                ->where('subscription_id', $this->viewingSubscriptionId)
                ->latest()
                ->limit(20)
                ->get();
        }

        return view('livewire.admin.developers.index', [
            'subscriptions' => $subscriptions,
            'deliveries' => $deliveries,
        ])->layout('layouts.admin', ['title' => 'Developers']);
    }
}
