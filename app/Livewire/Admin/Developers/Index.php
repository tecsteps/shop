<?php

namespace App\Livewire\Admin\Developers;

use App\Enums\WebhookSubscriptionStatus;
use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Store;
use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use App\Services\WebhookService;
use App\Support\TokenAbilities;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Developer settings (spec 03 section 16): personal access tokens for the
 * Admin REST API and webhook subscription management. Tokens and webhook
 * signing secrets are displayed exactly once after generation.
 */
#[Layout('layouts::admin')]
class Index extends Component
{
    use AuthorizesRequests, SendsToasts;

    public string $newTokenName = '';

    /** @var list<string> */
    public array $newTokenAbilities = [];

    public ?string $generatedToken = null;

    public ?int $editingWebhookId = null;

    public string $webhookEventType = 'order.created';

    public string $webhookUrl = '';

    public ?string $generatedWebhookSecret = null;

    public function mount(): void
    {
        $this->authorize('manageDevelopers', $this->store());
    }

    public function generateToken(): void
    {
        $this->authorize('manageDevelopers', $this->store());

        $this->validate([
            'newTokenName' => ['required', 'string', 'max:255'],
            'newTokenAbilities' => ['required', 'array', 'min:1'],
            'newTokenAbilities.*' => [Rule::in(TokenAbilities::names())],
        ], [], [
            'newTokenName' => __('token name'),
            'newTokenAbilities' => __('abilities'),
        ]);

        $token = auth()->user()->createToken($this->newTokenName, $this->newTokenAbilities);

        $this->generatedToken = $token->plainTextToken;

        Flux::modal('generate-token')->close();

        $this->reset('newTokenName', 'newTokenAbilities');
        unset($this->tokens);

        $this->toast(__('API token created.'));
    }

    public function revokeToken(int $tokenId): void
    {
        $this->authorize('manageDevelopers', $this->store());

        auth()->user()->tokens()->whereKey($tokenId)->delete();

        unset($this->tokens);

        $this->toast(__('API token revoked.'));
    }

    public function openWebhookModal(?int $webhookId = null): void
    {
        $this->authorize('manageDevelopers', $this->store());

        $this->resetErrorBag();

        if ($webhookId !== null) {
            $webhook = WebhookSubscription::query()->findOrFail($webhookId);

            $this->editingWebhookId = $webhook->getKey();
            $this->webhookEventType = $webhook->event_type;
            $this->webhookUrl = $webhook->target_url;
        } else {
            $this->editingWebhookId = null;
            $this->webhookEventType = 'order.created';
            $this->webhookUrl = '';
        }

        Flux::modal('webhook-form')->show();
    }

    public function saveWebhook(): void
    {
        $this->authorize('manageDevelopers', $this->store());

        $this->validate([
            'webhookEventType' => ['required', Rule::in(WebhookService::EVENT_TYPES)],
            'webhookUrl' => ['required', 'url:https,http', 'max:2048'],
        ], [], [
            'webhookEventType' => __('event type'),
            'webhookUrl' => __('endpoint URL'),
        ]);

        if ($this->editingWebhookId !== null) {
            $webhook = WebhookSubscription::query()->findOrFail($this->editingWebhookId);

            $webhook->update([
                'event_type' => $this->webhookEventType,
                'target_url' => $this->webhookUrl,
            ]);

            $this->toast(__('Webhook updated.'));
        } else {
            $secret = 'whsec_'.Str::random(32);

            WebhookSubscription::query()->create([
                'store_id' => $this->store()->getKey(),
                'event_type' => $this->webhookEventType,
                'target_url' => $this->webhookUrl,
                'signing_secret_encrypted' => $secret,
                'status' => WebhookSubscriptionStatus::Active,
            ]);

            $this->generatedWebhookSecret = $secret;

            $this->toast(__('Webhook created.'));
        }

        Flux::modal('webhook-form')->close();

        $this->reset('editingWebhookId', 'webhookUrl');
        $this->webhookEventType = 'order.created';
        unset($this->webhooks, $this->recentDeliveries);
    }

    /**
     * Pause an active subscription or resume a paused one. Resuming resets
     * the circuit breaker counter (spec 05 section 13.4: manual re-enable).
     */
    public function toggleWebhookStatus(int $webhookId): void
    {
        $this->authorize('manageDevelopers', $this->store());

        $webhook = WebhookSubscription::query()->findOrFail($webhookId);

        if ($webhook->status === WebhookSubscriptionStatus::Active) {
            $webhook->update(['status' => WebhookSubscriptionStatus::Paused]);

            $this->toast(__('Webhook paused.'));
        } else {
            $webhook->update([
                'status' => WebhookSubscriptionStatus::Active,
                'consecutive_failures' => 0,
            ]);

            $this->toast(__('Webhook resumed.'));
        }

        unset($this->webhooks);
    }

    public function deleteWebhook(int $webhookId): void
    {
        $this->authorize('manageDevelopers', $this->store());

        WebhookSubscription::query()->whereKey($webhookId)->delete();

        unset($this->webhooks, $this->recentDeliveries);

        $this->toast(__('Webhook deleted.'));
    }

    /**
     * @return Collection<int, \Laravel\Sanctum\PersonalAccessToken>
     */
    #[Computed]
    public function tokens(): Collection
    {
        return auth()->user()->tokens()->latest('id')->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function availableAbilities(): array
    {
        return TokenAbilities::all();
    }

    /**
     * @return Collection<int, WebhookSubscription>
     */
    #[Computed]
    public function webhooks(): Collection
    {
        return WebhookSubscription::query()
            ->with('latestDelivery')
            ->orderBy('id')
            ->get();
    }

    /**
     * The ten most recent delivery attempts across all of the store's
     * subscriptions, for the recent-deliveries panel.
     *
     * @return Collection<int, WebhookDelivery>
     */
    #[Computed]
    public function recentDeliveries(): Collection
    {
        return WebhookDelivery::query()
            ->whereHas('subscription')
            ->with('subscription')
            ->whereNotNull('last_attempt_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function webhookEventTypes(): array
    {
        return WebhookService::EVENT_TYPES;
    }

    public function render(): View
    {
        return view('livewire.admin.developers.index')->title(__('Developers'));
    }

    protected function store(): Store
    {
        return app('current_store');
    }
}
