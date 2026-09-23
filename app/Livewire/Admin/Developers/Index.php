<?php

namespace App\Livewire\Admin\Developers;

use App\Models\PersonalAccessToken;
use App\Models\Store;
use App\Models\User;
use App\Models\WebhookSubscription;
use App\Services\ApiTokenService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    private const WEBHOOK_EVENT_TYPES = [
        'order.created',
        'order.updated',
        'order.cancelled',
        'product.created',
        'product.updated',
        'product.deleted',
        'customer.created',
        'checkout.completed',
        'fulfillment.created',
        'refund.created',
    ];

    private const WEBHOOK_STATUSES = ['active', 'paused', 'disabled'];

    public string $newTokenName = '';

    /** @var list<string> */
    public array $tokenAbilities = ['read-products'];

    public string $tokenExpiresAt = '';

    #[Locked]
    public ?string $generatedToken = null;

    public bool $showGenerateTokenModal = false;

    public bool $showWebhookModal = false;

    public ?int $editingWebhookId = null;

    public string $webhookEventType = '';

    public string $webhookUrl = '';

    public string $webhookStatus = 'active';

    public function mount(): void
    {
        Gate::authorize('manage-developers');
    }

    public function openTokenModal(): void
    {
        Gate::authorize('manage-developers');
        $this->resetValidation();
        $this->newTokenName = '';
        $this->tokenAbilities = ['read-products'];
        $this->tokenExpiresAt = '';
        $this->showGenerateTokenModal = true;
    }

    public function generateToken(ApiTokenService $apiTokens): void
    {
        Gate::authorize('manage-developers');
        $store = $this->currentStore();
        $allowedAbilities = $apiTokens->availableAbilities();

        $validated = $this->validate([
            'newTokenName' => ['required', 'string', 'max:255'],
            'tokenAbilities' => ['required', 'array', 'min:1'],
            'tokenAbilities.*' => ['required', 'string', 'distinct', Rule::in($allowedAbilities)],
            'tokenExpiresAt' => ['nullable', 'date', 'after:today'],
        ], [
            'tokenAbilities.min' => 'Select at least one API permission.',
            'tokenExpiresAt.after' => 'Choose an expiry date after today.',
        ]);

        $expiresAt = filled($validated['tokenExpiresAt'])
            ? CarbonImmutable::parse($validated['tokenExpiresAt'])->endOfDay()
            : null;

        $issued = $apiTokens->create(
            auth()->user(),
            $store,
            $validated['newTokenName'],
            $validated['tokenAbilities'],
            $expiresAt,
        );

        $this->generatedToken = $issued['plain_text_token'];
        $this->showGenerateTokenModal = false;
        $this->newTokenName = '';
        $this->tokenAbilities = ['read-products'];
        $this->tokenExpiresAt = '';
        session()->flash('status', 'API token created. Copy it now; it will not be shown again.');
    }

    public function dismissGeneratedToken(): void
    {
        Gate::authorize('manage-developers');
        $this->generatedToken = null;
    }

    public function revokeToken(int $tokenId, ApiTokenService $apiTokens): void
    {
        Gate::authorize('manage-developers');
        $token = PersonalAccessToken::query()
            ->where('store_id', $this->currentStore()->getKey())
            ->findOrFail($tokenId);

        $apiTokens->revoke($token, $this->currentStore());
        $this->generatedToken = null;
        session()->flash('status', 'API token revoked.');
    }

    public function openWebhookModal(?int $webhookId = null): void
    {
        Gate::authorize('manage-developers');
        $this->resetValidation();
        $this->editingWebhookId = null;
        $this->webhookEventType = '';
        $this->webhookUrl = '';
        $this->webhookStatus = 'active';

        if ($webhookId !== null) {
            $webhook = $this->webhookForCurrentStore($webhookId);
            $this->editingWebhookId = $webhook->id;
            $this->webhookEventType = $webhook->event_type;
            $this->webhookUrl = $webhook->target_url;
            $this->webhookStatus = $webhook->status;
        }

        $this->showWebhookModal = true;
    }

    public function saveWebhook(): void
    {
        Gate::authorize('manage-developers');
        $store = $this->currentStore();

        $validated = $this->validate([
            'webhookEventType' => ['required', Rule::in(self::WEBHOOK_EVENT_TYPES)],
            'webhookUrl' => ['required', 'string', 'url:http,https', 'max:2048'],
            'webhookStatus' => ['required', Rule::in(self::WEBHOOK_STATUSES)],
        ], [
            'webhookEventType.in' => 'Select a supported webhook event.',
            'webhookUrl.url' => 'Enter a valid HTTP or HTTPS endpoint URL.',
            'webhookStatus.in' => 'Select a supported webhook status.',
        ]);

        $attributes = [
            'event_type' => $validated['webhookEventType'],
            'target_url' => $validated['webhookUrl'],
            'status' => $validated['webhookStatus'],
        ];

        if ($this->editingWebhookId === null) {
            WebhookSubscription::query()->create([
                ...$attributes,
                'store_id' => $store->getKey(),
                'app_installation_id' => null,
                'signing_secret_encrypted' => bin2hex(random_bytes(32)),
            ]);
        } else {
            $this->webhookForCurrentStore($this->editingWebhookId)->update($attributes);
        }

        $this->showWebhookModal = false;
        $this->resetWebhookForm();
        session()->flash('status', 'Webhook subscription saved.');
    }

    public function deleteWebhook(int $webhookId): void
    {
        Gate::authorize('manage-developers');
        $this->webhookForCurrentStore($webhookId)->delete();

        if ($this->editingWebhookId === $webhookId) {
            $this->showWebhookModal = false;
            $this->resetWebhookForm();
        }

        session()->flash('status', 'Webhook subscription deleted.');
    }

    public function render(): mixed
    {
        Gate::authorize('manage-developers');
        $storeId = $this->currentStore()->getKey();

        return view('livewire.admin.developers.index', [
            'tokens' => PersonalAccessToken::query()
                ->where('store_id', $storeId)
                ->where('tokenable_type', (new User)->getMorphClass())
                ->with('tokenable:id,name')
                ->orderByDesc('created_at')
                ->get(),
            'webhooks' => WebhookSubscription::query()
                ->where('store_id', $storeId)
                ->with('latestDelivery')
                ->orderByDesc('created_at')
                ->get(),
            'tokenAbilityOptions' => app(ApiTokenService::class)->availableAbilities(),
            'webhookEventTypes' => self::WEBHOOK_EVENT_TYPES,
        ])->layout('layouts.admin');
    }

    private function currentStore(): Store
    {
        $store = app()->bound('current_store') ? app('current_store') : null;
        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function webhookForCurrentStore(int $webhookId): WebhookSubscription
    {
        return WebhookSubscription::query()
            ->where('store_id', $this->currentStore()->getKey())
            ->findOrFail($webhookId);
    }

    private function resetWebhookForm(): void
    {
        $this->editingWebhookId = null;
        $this->webhookEventType = '';
        $this->webhookUrl = '';
        $this->webhookStatus = 'active';
    }
}
