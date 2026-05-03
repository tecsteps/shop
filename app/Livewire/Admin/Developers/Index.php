<?php

namespace App\Livewire\Admin\Developers;

use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\ApiToken;
use App\Models\WebhookSubscription;
use App\Services\ApiTokenService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    use UsesAdminStore;

    public string $newTokenName = '';

    public ?string $generatedToken = null;

    public string $webhookEventType = 'order.created';

    public string $webhookUrl = '';

    public ?int $editingWebhookId = null;

    /**
     * @var list<string>
     */
    public array $tokenAbilities = ['read-analytics'];

    /**
     * @var list<string>
     */
    public array $availableAbilities = [
        'read-products',
        'read-orders',
        'read-customers',
        'read-analytics',
    ];

    /**
     * @var list<string>
     */
    public array $webhookEventTypes = [
        'order.created',
        'order.paid',
        'order.fulfilled',
        'order.refunded',
        'product.created',
        'product.updated',
        'product.deleted',
        'checkout.completed',
    ];

    public function generateToken(ApiTokenService $tokens): void
    {
        $validated = $this->validate([
            'newTokenName' => ['required', 'string', 'max:255'],
            'tokenAbilities' => ['required', 'array', 'min:1'],
            'tokenAbilities.*' => [Rule::in($this->availableAbilities)],
        ]);

        $result = $tokens->create(
            $this->currentStore(),
            $this->currentUser(),
            $validated['newTokenName'],
            $validated['tokenAbilities'],
        );

        $this->generatedToken = $result['plain_text_token'];
        $this->reset('newTokenName');
        $this->tokenAbilities = ['read-analytics'];
        $this->notify('API token generated.');
    }

    public function revokeToken(int $tokenId, ApiTokenService $tokens): void
    {
        $token = ApiToken::withoutGlobalScopes()
            ->where('store_id', $this->currentStore()->id)
            ->whereKey($tokenId)
            ->firstOrFail();

        $tokens->revoke($token);
        $this->notify('API token revoked.');
    }

    public function editWebhook(int $webhookId): void
    {
        $webhook = WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $this->currentStore()->id)
            ->whereKey($webhookId)
            ->firstOrFail();

        $this->editingWebhookId = $webhook->id;
        $this->webhookEventType = $webhook->event_type;
        $this->webhookUrl = $webhook->target_url;
    }

    public function saveWebhook(): void
    {
        $validated = $this->validate([
            'webhookEventType' => ['required', Rule::in($this->webhookEventTypes)],
            'webhookUrl' => ['required', 'url', 'max:2048'],
        ]);

        $attributes = [
            'event_type' => $validated['webhookEventType'],
            'target_url' => $validated['webhookUrl'],
            'status' => 'active',
            'consecutive_failures' => 0,
        ];

        if ($this->editingWebhookId) {
            WebhookSubscription::withoutGlobalScopes()
                ->where('store_id', $this->currentStore()->id)
                ->whereKey($this->editingWebhookId)
                ->firstOrFail()
                ->update($attributes);
        } else {
            WebhookSubscription::withoutGlobalScopes()->create([
                ...$attributes,
                'store_id' => $this->currentStore()->id,
                'signing_secret_encrypted' => str()->random(40),
            ]);
        }

        $this->reset('editingWebhookId', 'webhookUrl');
        $this->webhookEventType = 'order.created';
        $this->notify('Webhook saved.');
    }

    public function deleteWebhook(int $webhookId): void
    {
        WebhookSubscription::withoutGlobalScopes()
            ->where('store_id', $this->currentStore()->id)
            ->whereKey($webhookId)
            ->delete();

        $this->notify('Webhook deleted.');
    }

    public function render(): View
    {
        return view('livewire.admin.developers.index', [
            'tokens' => ApiToken::withoutGlobalScopes()
                ->where('store_id', $this->currentStore()->id)
                ->latest()
                ->get(),
            'webhooks' => WebhookSubscription::withoutGlobalScopes()
                ->where('store_id', $this->currentStore()->id)
                ->latest()
                ->get(),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Developers',
        ]);
    }
}
