<?php

namespace App\Livewire\Admin\Developers;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\WebhookSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    use DispatchesToasts;

    #[Layout('layouts.admin.app')]
    public string $newTokenName = '';

    public ?string $generatedToken = null;

    public bool $showGenerateToken = false;

    public bool $showWebhookModal = false;

    public ?int $editingWebhookId = null;

    public string $webhookEventType = 'order.created';

    public string $webhookUrl = '';

    public function mount(): void
    {
        $this->authorize('viewSettings', app('current_store'));
    }

    #[Computed]
    public function tokens(): Collection
    {
        return auth()->user()->tokens()->get();
    }

    #[Computed]
    public function webhooks(): Collection
    {
        return app('current_store')->webhookSubscriptions()->latest('id')->get();
    }

    public function generateToken(): void
    {
        $this->authorize('viewSettings', app('current_store'));

        $this->validate([
            'newTokenName' => ['required', 'string', 'max:255'],
        ]);

        $token = auth()->user()->createToken($this->newTokenName, [
            'read-products',
            'write-products',
            'read-orders',
            'write-orders',
            'read-collections',
            'write-collections',
            'read-customers',
        ]);

        $this->generatedToken = $token->plainTextToken;
        $this->showGenerateToken = false;
        $this->newTokenName = '';
    }

    public function revokeToken(int $tokenId): void
    {
        $this->authorize('viewSettings', app('current_store'));

        auth()->user()->tokens()->where('id', $tokenId)->delete();

        $this->toast('Token revoked');
    }

    public function openWebhookModal(?int $webhookId = null): void
    {
        $this->editingWebhookId = $webhookId;
        $this->webhookEventType = 'order.created';
        $this->webhookUrl = '';

        if ($webhookId) {
            $webhook = WebhookSubscription::find($webhookId);

            if ($webhook) {
                $this->webhookEventType = $webhook->event_type;
                $this->webhookUrl = $webhook->target_url;
            }
        }

        $this->showWebhookModal = true;
    }

    public function saveWebhook(): void
    {
        $this->authorize('viewSettings', app('current_store'));

        $this->validate([
            'webhookEventType' => ['required', Rule::in($this->eventTypes())],
            'webhookUrl' => ['required', 'url'],
        ]);

        $data = [
            'store_id' => app('current_store')->id,
            'event_type' => $this->webhookEventType,
            'target_url' => $this->webhookUrl,
            'status' => 'active',
        ];

        if ($this->editingWebhookId) {
            WebhookSubscription::withoutTimestamps(fn () => WebhookSubscription::where('id', $this->editingWebhookId)
                ->where('store_id', app('current_store')->id)
                ->update($data));
        } else {
            WebhookSubscription::withoutTimestamps(fn () => WebhookSubscription::create($data + ['signing_secret_encrypted' => Str::random(32)]));
        }

        $this->showWebhookModal = false;
        $this->toast('Webhook saved');
    }

    public function deleteWebhook(int $webhookId): void
    {
        $this->authorize('viewSettings', app('current_store'));

        WebhookSubscription::where('id', $webhookId)
            ->where('store_id', app('current_store')->id)
            ->delete();

        $this->toast('Webhook deleted');
    }

    /**
     * @return list<string>
     */
    public function eventTypes(): array
    {
        return [
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
    }

    public function render()
    {
        return view('livewire.admin.developers.index');
    }
}
