<?php

namespace App\Livewire\Admin\Developers;

use App\Enums\WebhookEventType;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\OauthToken;
use App\Models\Store;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $storeId;

    public string $newTokenName = '';

    public ?string $generatedToken = null;

    public string $webhookEventType = 'order.created';

    public string $webhookUrl = '';

    public ?int $editingWebhookId = null;

    /**
     * @var list<string>
     */
    public array $tokenAbilities = [
        'read-products',
        'write-products',
        'read-orders',
        'write-orders',
        'read-customers',
        'write-customers',
        'read-analytics',
    ];

    public function mount(): void
    {
        $store = $this->store();

        $this->authorize('update', $store);
        $this->storeId = $store->getKey();
    }

    public function generateToken(WebhookService $webhooks): void
    {
        $this->authorize('update', $this->scopedStore());

        $this->validate([
            'newTokenName' => ['required', 'string', 'max:255'],
        ], [], [
            'newTokenName' => 'token name',
        ]);

        $result = $webhooks->createApiToken($this->scopedStore(), $this->newTokenName, $this->tokenAbilities);

        $this->generatedToken = $result['plain_text'];
        $this->newTokenName = '';
        $this->modal('generate-token')->close();
        $this->dispatch('toast', type: 'success', message: __('API token generated'));
    }

    public function revokeToken(int $tokenId): void
    {
        $this->authorize('update', $this->scopedStore());

        $this->token($tokenId)->delete();

        session()->flash('status', __('API token revoked'));
        $this->dispatch('toast', type: 'success', message: __('API token revoked'));
    }

    public function openWebhookModal(?int $webhookId = null): void
    {
        $this->editingWebhookId = $webhookId;

        if ($webhookId !== null) {
            $webhook = $this->webhook($webhookId);
            $this->webhookEventType = $webhook->event_type->value;
            $this->webhookUrl = $webhook->target_url;
        } else {
            $this->webhookEventType = WebhookEventType::OrderCreated->value;
            $this->webhookUrl = '';
        }

        $this->modal('webhook-form')->show();
    }

    public function saveWebhook(WebhookService $webhooks): void
    {
        $this->authorize('update', $this->scopedStore());

        $this->validate([
            'webhookEventType' => ['required', Rule::enum(WebhookEventType::class)],
            'webhookUrl' => ['required', 'url', 'max:2048'],
        ], [], [
            'webhookEventType' => 'event type',
            'webhookUrl' => 'endpoint URL',
        ]);

        $webhook = $this->editingWebhookId !== null
            ? $this->webhook($this->editingWebhookId)
            : new WebhookSubscription([
                'store_id' => $this->storeId,
                'signing_secret_encrypted' => $webhooks->createSigningSecret(),
            ]);

        $webhook->forceFill([
            'event_type' => $this->webhookEventType,
            'target_url' => $this->webhookUrl,
            'status' => WebhookSubscriptionStatus::Active,
        ])->save();

        $this->editingWebhookId = null;
        $this->webhookUrl = '';
        $this->modal('webhook-form')->close();
        $this->dispatch('toast', type: 'success', message: __('Webhook saved'));
    }

    public function deleteWebhook(int $webhookId): void
    {
        $this->authorize('update', $this->scopedStore());

        $this->webhook($webhookId)->delete();
        $this->dispatch('toast', type: 'success', message: __('Webhook deleted'));
    }

    /**
     * @return Collection<int, OauthToken>
     */
    public function tokens(): Collection
    {
        return OauthToken::query()
            ->with('installation.app')
            ->whereHas('installation', function ($query): void {
                $query->withoutGlobalScopes()->where('store_id', $this->storeId);
            })
            ->latest('created_at')
            ->get();
    }

    /**
     * @return Collection<int, WebhookSubscription>
     */
    public function webhooks(): Collection
    {
        return WebhookSubscription::withoutGlobalScopes()
            ->withCount([
                'deliveries',
                'deliveries as failed_deliveries_count' => fn ($query) => $query->where('status', 'failed'),
            ])
            ->where('store_id', $this->storeId)
            ->orderBy('event_type')
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.developers.index', [
            'tokens' => $this->tokens(),
            'webhooks' => $this->webhooks(),
            'eventTypes' => WebhookEventType::selectable(),
        ])->layout('layouts.app', [
            'title' => __('Developers'),
        ]);
    }

    private function token(int $tokenId): OauthToken
    {
        return OauthToken::query()
            ->whereHas('installation', function ($query): void {
                $query->withoutGlobalScopes()->where('store_id', $this->storeId);
            })
            ->findOrFail($tokenId);
    }

    private function webhook(int $webhookId): WebhookSubscription
    {
        return WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->findOrFail($webhookId);
    }

    private function store(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function scopedStore(): Store
    {
        return Store::query()->findOrFail($this->storeId);
    }
}
