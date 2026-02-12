<?php

namespace App\Livewire\Admin\Developers;

use App\Models\WebhookSubscription;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Developer Settings')]
class Index extends Component
{
    public bool $showWebhookModal = false;

    public ?int $editingWebhookId = null;

    public string $webhookUrl = '';

    public string $webhookTopic = '';

    public string $webhookSecret = '';

    public bool $webhookActive = true;

    public function openWebhookModal(?int $webhookId = null): void
    {
        $this->editingWebhookId = $webhookId;

        if ($webhookId) {
            $webhook = WebhookSubscription::findOrFail($webhookId);
            $this->webhookUrl = $webhook->url;
            $this->webhookTopic = $webhook->topic;
            $this->webhookSecret = $webhook->secret ?? '';
            $this->webhookActive = $webhook->is_active;
        } else {
            $this->webhookUrl = '';
            $this->webhookTopic = '';
            $this->webhookSecret = Str::random(32);
            $this->webhookActive = true;
        }

        $this->showWebhookModal = true;
    }

    public function saveWebhook(): void
    {
        $this->validate([
            'webhookUrl' => ['required', 'url', 'max:500'],
            'webhookTopic' => ['required', 'string', 'max:100'],
            'webhookSecret' => ['nullable', 'string', 'max:255'],
        ]);

        $store = app('current_store');

        $data = [
            'store_id' => $store->id,
            'url' => $this->webhookUrl,
            'topic' => $this->webhookTopic,
            'secret' => $this->webhookSecret,
            'is_active' => $this->webhookActive,
        ];

        if ($this->editingWebhookId) {
            WebhookSubscription::where('id', $this->editingWebhookId)
                ->where('store_id', $store->id)
                ->update($data);
        } else {
            WebhookSubscription::create($data);
        }

        $this->showWebhookModal = false;
        $this->dispatch('toast', type: 'success', message: 'Webhook saved.');
    }

    public function deleteWebhook(int $webhookId): void
    {
        $store = app('current_store');

        WebhookSubscription::where('id', $webhookId)
            ->where('store_id', $store->id)
            ->delete();

        $this->dispatch('toast', type: 'success', message: 'Webhook deleted.');
    }

    public function toggleWebhook(int $webhookId): void
    {
        $store = app('current_store');

        $webhook = WebhookSubscription::where('id', $webhookId)
            ->where('store_id', $store->id)
            ->firstOrFail();

        $webhook->update(['is_active' => ! $webhook->is_active]);

        $this->dispatch('toast', type: 'success', message: $webhook->is_active ? 'Webhook enabled.' : 'Webhook disabled.');
    }

    public function render(): View
    {
        $store = app('current_store');

        $webhooks = WebhookSubscription::query()
            ->where('store_id', $store->id)
            ->latest()
            ->get();

        return view('livewire.admin.developers.index', [
            'webhooks' => $webhooks,
        ]);
    }
}
