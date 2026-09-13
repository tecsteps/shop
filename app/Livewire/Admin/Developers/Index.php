<?php

namespace App\Livewire\Admin\Developers;

use App\Enums\WebhookSubscriptionStatus;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\WebhookSubscription;
use App\Services\RolePermissionMap;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    use ResolvesAdminStore;

    public string $newTokenName = '';

    /** @var array<int, string> */
    public array $newTokenAbilities = [];

    public ?string $generatedToken = null;

    public bool $showTokenModal = false;

    public ?int $editingWebhookId = null;

    public string $webhookEventType = 'order.created';

    public string $webhookUrl = '';

    public bool $showWebhookModal = false;

    public function mount(): void
    {
        Gate::authorize('manage-developers');
    }

    public function generateToken(): void
    {
        Gate::authorize('manage-developers');

        $validated = $this->validate([
            'newTokenName' => 'required|string|max:255',
            'newTokenAbilities' => 'nullable|array',
        ]);

        if (in_array('manage-platform', $validated['newTokenAbilities'] ?? [], true)
            && Gate::denies('manage-platform')) {
            $this->addError('newTokenAbilities', 'Only store owners can issue tokens with the manage-platform ability.');

            return;
        }

        $token = auth()->user()->createToken(
            $validated['newTokenName'],
            array_map('strval', $validated['newTokenAbilities'] ?? ['*'])
        );

        $this->generatedToken = $token->plainTextToken;
        $this->newTokenName = '';
        $this->newTokenAbilities = [];
        $this->showTokenModal = false;
    }

    public function revokeToken(int $tokenId): void
    {
        Gate::authorize('manage-developers');

        auth()->user()->tokens()->whereKey($tokenId)->delete();

        $this->toast('success', 'Token revoked.');
    }

    public function dismissGeneratedToken(): void
    {
        $this->generatedToken = null;
    }

    public function openWebhookModal(?int $webhookId = null): void
    {
        Gate::authorize('manage-developers');

        $this->editingWebhookId = $webhookId;

        if ($webhookId !== null) {
            $webhook = $this->findWebhook($webhookId);
            $this->webhookEventType = $webhook->event_type;
            $this->webhookUrl = $webhook->target_url;
        } else {
            $this->webhookEventType = 'order.created';
            $this->webhookUrl = '';
        }

        $this->showWebhookModal = true;
    }

    public function saveWebhook(): void
    {
        Gate::authorize('manage-developers');

        $validated = $this->validate([
            'webhookEventType' => 'required|string|in:'.implode(',', WebhookService::EVENT_TYPES),
            'webhookUrl' => 'required|url|max:2048',
        ]);

        if ($this->editingWebhookId !== null) {
            $this->findWebhook($this->editingWebhookId)->update([
                'event_type' => $validated['webhookEventType'],
                'target_url' => $validated['webhookUrl'],
            ]);
        } else {
            app(WebhookService::class)->subscribe(
                $this->store(),
                $validated['webhookEventType'],
                $validated['webhookUrl'],
            );
        }

        $this->showWebhookModal = false;
        $this->toast('success', 'Webhook saved.');
    }

    public function deleteWebhook(int $webhookId): void
    {
        Gate::authorize('manage-developers');

        $this->findWebhook($webhookId)->delete();

        $this->toast('success', 'Webhook deleted.');
    }

    public function toggleWebhookStatus(int $webhookId): void
    {
        Gate::authorize('manage-developers');

        $webhook = $this->findWebhook($webhookId);

        $webhook->update([
            'status' => $webhook->status === WebhookSubscriptionStatus::Active
                ? WebhookSubscriptionStatus::Paused
                : WebhookSubscriptionStatus::Active,
        ]);

        $this->toast('success', 'Webhook status updated.');
    }

    public function render()
    {
        return view('livewire.admin.developers.index', [
            'tokens' => auth()->user()->tokens()->orderByDesc('created_at')->get(),
            'webhooks' => WebhookSubscription::query()
                ->where('store_id', $this->store()->getKey())
                ->withCount('webhookDeliveries')
                ->orderBy('event_type')
                ->orderBy('id')
                ->get(),
            'abilities' => RolePermissionMap::allAbilities(),
            'eventTypes' => WebhookService::EVENT_TYPES,
        ])->layout('livewire.admin.layout.app', ['title' => 'Developers']);
    }

    protected function findWebhook(int $webhookId): WebhookSubscription
    {
        return WebhookSubscription::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($webhookId)
            ->firstOrFail();
    }
}
