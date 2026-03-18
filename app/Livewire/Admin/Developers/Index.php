<?php

namespace App\Livewire\Admin\Developers;

use App\Models\WebhookSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Index extends Component
{
    public string $newTokenName = '';

    public ?string $generatedToken = null;

    public string $webhookEventType = 'order.created';

    public string $webhookUrl = '';

    public ?int $editingWebhookId = null;

    public bool $showWebhookModal = false;

    public function getTokensProperty(): Collection
    {
        $user = auth()->user();

        return $user->tokens()->latest()->get();
    }

    public function getWebhooksProperty(): Collection
    {
        $store = app('current_store');

        return WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereNull('app_installation_id')
            ->latest()
            ->get();
    }

    public function generateToken(): void
    {
        $this->validate([
            'newTokenName' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $token = $user->createToken($this->newTokenName);

        $this->generatedToken = $token->plainTextToken;
        $this->newTokenName = '';

        $this->dispatch('toast', type: 'success', message: __('Token generated successfully.'));
    }

    public function revokeToken(int $tokenId): void
    {
        $user = auth()->user();
        $user->tokens()->where('id', $tokenId)->delete();

        $this->dispatch('toast', type: 'success', message: __('Token revoked.'));
    }

    public function openWebhookModal(?int $webhookId = null): void
    {
        if ($webhookId) {
            $store = app('current_store');
            $webhook = WebhookSubscription::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->findOrFail($webhookId);

            $this->editingWebhookId = $webhook->id;
            $this->webhookEventType = $webhook->event_type;
            $this->webhookUrl = $webhook->target_url;
        } else {
            $this->editingWebhookId = null;
            $this->webhookEventType = 'order.created';
            $this->webhookUrl = '';
        }

        $this->showWebhookModal = true;
    }

    public function saveWebhook(): void
    {
        $this->validate([
            'webhookEventType' => 'required|string',
            'webhookUrl' => 'required|url',
        ]);

        $store = app('current_store');

        if ($this->editingWebhookId) {
            $webhook = WebhookSubscription::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->findOrFail($this->editingWebhookId);

            $webhook->update([
                'event_type' => $this->webhookEventType,
                'target_url' => $this->webhookUrl,
            ]);
        } else {
            WebhookSubscription::withoutGlobalScopes()->create([
                'store_id' => $store->id,
                'event_type' => $this->webhookEventType,
                'target_url' => $this->webhookUrl,
                'signing_secret_encrypted' => Str::random(32),
                'status' => 'active',
            ]);
        }

        $this->showWebhookModal = false;
        $this->resetWebhookForm();

        $this->dispatch('toast', type: 'success', message: __('Webhook saved.'));
    }

    public function deleteWebhook(int $webhookId): void
    {
        $store = app('current_store');

        WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('id', $webhookId)
            ->delete();

        $this->dispatch('toast', type: 'success', message: __('Webhook deleted.'));
    }

    private function resetWebhookForm(): void
    {
        $this->editingWebhookId = null;
        $this->webhookEventType = 'order.created';
        $this->webhookUrl = '';
    }

    public function render(): View
    {
        return view('livewire.admin.developers.index');
    }
}
