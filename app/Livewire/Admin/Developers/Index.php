<?php

namespace App\Livewire\Admin\Developers;

use App\Enums\WebhookSubscriptionStatus;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\ApiTokenService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Developers page: API tokens and webhook subscriptions (spec 03 §16).
 */
class Index extends Component
{
    /**
     * Token abilities offered in the create form (spec 06 §1.3).
     *
     * @var array<string, string>
     */
    public const ABILITIES = [
        'read-products' => 'Read products',
        'write-products' => 'Write products',
        'read-orders' => 'Read orders',
        'write-orders' => 'Write orders',
        'read-customers' => 'Read customers',
        'write-customers' => 'Write customers',
        'read-collections' => 'Read collections',
        'write-collections' => 'Write collections',
        'read-discounts' => 'Read discounts',
        'write-discounts' => 'Write discounts',
        'read-analytics' => 'Read analytics',
        'read-settings' => 'Read settings',
        'write-settings' => 'Write settings',
        'read-themes' => 'Read themes',
        'write-themes' => 'Write themes',
        'read-content' => 'Read content',
        'write-content' => 'Write content',
        'manage-platform' => 'Manage platform',
    ];

    /**
     * Webhook event types that can be subscribed to (spec 05 §13.1).
     *
     * @var list<string>
     */
    public const EVENT_TYPES = [
        'order.created',
        'order.paid',
        'order.fulfilled',
        'order.refunded',
        'product.created',
        'product.updated',
        'product.deleted',
        'checkout.completed',
    ];

    public string $newTokenName = '';

    /** @var list<string> */
    public array $newTokenAbilities = [];

    public string $newTokenExpiresAt = '';

    public ?string $generatedToken = null;

    public bool $showTokenModal = false;

    public string $webhookEventType = 'order.created';

    public string $webhookUrl = '';

    public ?int $editingWebhookId = null;

    public bool $showWebhookModal = false;

    public ?string $generatedWebhookSecret = null;

    public ?int $viewingDeliveriesFor = null;

    public bool $showDeliveriesModal = false;

    public function mount(): void
    {
        Gate::authorize('manage-developers');
    }

    /**
     * Create a new API token; the plain value is shown once (spec 03 §16).
     */
    public function generateToken(ApiTokenService $tokens): void
    {
        Gate::authorize('manage-developers');

        $validated = $this->validate([
            'newTokenName' => ['required', 'string', 'max:255'],
            'newTokenAbilities' => ['required', 'array', 'min:1'],
            'newTokenAbilities.*' => ['string', Rule::in(array_keys(self::ABILITIES))],
            'newTokenExpiresAt' => ['nullable', 'date', 'after:today'],
        ]);

        $expiresAt = ($validated['newTokenExpiresAt'] ?? '') !== ''
            ? CarbonImmutable::parse($validated['newTokenExpiresAt'])->endOfDay()
            : null;

        $this->generatedToken = $tokens->create(
            $this->user(),
            $validated['newTokenName'],
            $validated['newTokenAbilities'],
            $expiresAt,
        );

        $this->reset('newTokenName', 'newTokenAbilities', 'newTokenExpiresAt');
        $this->showTokenModal = false;

        $this->dispatch('toast', type: 'success', message: 'API token created');
    }

    /**
     * Revoke (delete) one of the current user's tokens.
     */
    public function revokeToken(ApiTokenService $tokens, int $tokenId): void
    {
        Gate::authorize('manage-developers');

        $tokens->revoke($this->user(), $tokenId);

        $this->dispatch('toast', type: 'success', message: 'API token revoked');
    }

    /**
     * Open the webhook create/edit modal.
     */
    public function openWebhookModal(?int $webhookId = null): void
    {
        Gate::authorize('manage-developers');

        $this->resetValidation();
        $this->generatedWebhookSecret = null;

        if ($webhookId === null) {
            $this->editingWebhookId = null;
            $this->webhookEventType = self::EVENT_TYPES[0];
            $this->webhookUrl = '';
        } else {
            $webhook = WebhookSubscription::query()->findOrFail($webhookId);
            $this->editingWebhookId = $webhook->id;
            $this->webhookEventType = $webhook->event_type;
            $this->webhookUrl = $webhook->target_url;
        }

        $this->showWebhookModal = true;
    }

    /**
     * Create or update the webhook subscription. The signing secret is
     * generated on create and shown exactly once.
     */
    public function saveWebhook(): void
    {
        Gate::authorize('manage-developers');

        $validated = $this->validate([
            'webhookEventType' => ['required', Rule::in(self::EVENT_TYPES)],
            'webhookUrl' => ['required', 'url:https', 'max:2048'],
        ]);

        if ($this->editingWebhookId !== null) {
            $webhook = WebhookSubscription::query()->findOrFail($this->editingWebhookId);
            $webhook->forceFill([
                'event_type' => $validated['webhookEventType'],
                'target_url' => $validated['webhookUrl'],
            ])->save();

            $this->dispatch('toast', type: 'success', message: 'Webhook updated');
        } else {
            $secret = 'whsec_'.Str::random(32);

            WebhookSubscription::query()->create([
                'event_type' => $validated['webhookEventType'],
                'target_url' => $validated['webhookUrl'],
                'signing_secret_encrypted' => $secret,
                'status' => WebhookSubscriptionStatus::Active,
            ]);

            $this->generatedWebhookSecret = $secret;

            $this->dispatch('toast', type: 'success', message: 'Webhook created');
        }

        $this->showWebhookModal = false;
    }

    /**
     * Pause deliveries to the subscription (circuit breaker state or
     * manual pause). Manual action per spec 05 §13.4.
     */
    public function pauseWebhook(int $webhookId): void
    {
        Gate::authorize('manage-developers');

        WebhookSubscription::query()->findOrFail($webhookId)
            ->forceFill(['status' => WebhookSubscriptionStatus::Paused])
            ->save();

        $this->dispatch('toast', type: 'success', message: 'Webhook paused');
    }

    /**
     * Re-enable a paused subscription; resets the failure streak since
     * only failed deliveries count against it.
     */
    public function resumeWebhook(int $webhookId): void
    {
        Gate::authorize('manage-developers');

        WebhookSubscription::query()->findOrFail($webhookId)
            ->forceFill(['status' => WebhookSubscriptionStatus::Active])
            ->save();

        $this->dispatch('toast', type: 'success', message: 'Webhook resumed');
    }

    /**
     * Delete the subscription; its deliveries cascade away.
     */
    public function deleteWebhook(int $webhookId): void
    {
        Gate::authorize('manage-developers');

        WebhookSubscription::query()->findOrFail($webhookId)->delete();

        if ($this->viewingDeliveriesFor === $webhookId) {
            $this->viewingDeliveriesFor = null;
            $this->showDeliveriesModal = false;
        }

        $this->dispatch('toast', type: 'success', message: 'Webhook deleted');
    }

    /**
     * Open the deliveries log modal for a subscription.
     */
    public function viewDeliveries(int $webhookId): void
    {
        Gate::authorize('manage-developers');

        WebhookSubscription::query()->findOrFail($webhookId);

        $this->viewingDeliveriesFor = $webhookId;
        $this->showDeliveriesModal = true;
    }

    public function render(): View
    {
        $deliveries = $this->viewingDeliveriesFor !== null
            ? WebhookDelivery::query()
                ->where('subscription_id', $this->viewingDeliveriesFor)
                ->orderByDesc('id')
                ->limit(20)
                ->get()
            : collect();

        return view('livewire.admin.developers.index', [
            'tokens' => $this->user()->tokens()->orderByDesc('created_at')->get(),
            'webhooks' => WebhookSubscription::query()->orderByDesc('id')->get(),
            'deliveries' => $deliveries,
            'abilities' => self::ABILITIES,
            'eventTypes' => self::EVENT_TYPES,
        ])->layout('admin.layouts.app')->title('Developers');
    }

    /**
     * The authenticated admin user.
     */
    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
