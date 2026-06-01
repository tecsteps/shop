<?php

namespace App\Livewire\Admin\Developers;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Developer settings: Admin API personal access tokens (Sanctum) and webhook
 * subscriptions. The generated token is shown once. Restricted to roles that
 * may manage developer settings.
 */
#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use BindsCurrentStore;

    public bool $showTokenModal = false;

    public string $newTokenName = '';

    public ?string $generatedToken = null;

    public bool $showWebhookModal = false;

    public ?int $editingWebhookId = null;

    public string $webhookEventType = 'order.created';

    public string $webhookUrl = '';

    /**
     * Webhook event types offered in the form.
     */
    public const EVENT_TYPES = [
        'order.created', 'order.updated', 'order.cancelled',
        'product.created', 'product.updated', 'product.deleted',
        'customer.created', 'checkout.completed', 'fulfillment.created', 'refund.created',
    ];

    public function mount(): void
    {
        if (! Gate::allows('manage-developers')) {
            abort(403);
        }
    }

    public function getTokensProperty()
    {
        return Auth::guard('web')->user()->tokens()->orderByDesc('created_at')->get();
    }

    public function getWebhooksProperty()
    {
        return WebhookSubscription::query()
            ->where('store_id', app('current_store')->id)
            ->orderByDesc('id')
            ->get();
    }

    public function generateToken(): void
    {
        if (! Gate::allows('manage-developers')) {
            abort(403);
        }

        $this->validate(['newTokenName' => ['required', 'string', 'max:255']]);

        $token = Auth::guard('web')->user()->createToken($this->newTokenName, ['admin']);
        $this->generatedToken = $token->plainTextToken;

        $this->reset('newTokenName', 'showTokenModal');

        $this->dispatch('toast', type: 'success', message: __('Token generated'));
    }

    public function revokeToken(int $tokenId): void
    {
        if (! Gate::allows('manage-developers')) {
            abort(403);
        }

        Auth::guard('web')->user()->tokens()->whereKey($tokenId)->delete();
        $this->dispatch('toast', type: 'success', message: __('Token revoked'));
    }

    public function openWebhookModal(?int $webhookId = null): void
    {
        $this->resetValidation();
        $this->editingWebhookId = $webhookId;

        if ($webhookId !== null) {
            $webhook = WebhookSubscription::query()->where('store_id', app('current_store')->id)->findOrFail($webhookId);
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
        if (! Gate::allows('manage-developers')) {
            abort(403);
        }

        $this->validate([
            'webhookEventType' => ['required', Rule::in(self::EVENT_TYPES)],
            'webhookUrl' => ['required', 'url', 'max:2048'],
        ]);

        if ($this->editingWebhookId !== null) {
            WebhookSubscription::query()
                ->where('store_id', app('current_store')->id)
                ->whereKey($this->editingWebhookId)
                ->update(['event_type' => $this->webhookEventType, 'target_url' => $this->webhookUrl]);
        } else {
            WebhookSubscription::query()->create([
                'store_id' => app('current_store')->id,
                'app_installation_id' => null,
                'event_type' => $this->webhookEventType,
                'target_url' => $this->webhookUrl,
                'signing_secret_encrypted' => 'whsec_'.Str::random(32),
                'status' => 'active',
            ]);
        }

        $this->showWebhookModal = false;
        $this->dispatch('toast', type: 'success', message: __('Webhook saved'));
    }

    public function deleteWebhook(int $webhookId): void
    {
        if (! Gate::allows('manage-developers')) {
            abort(403);
        }

        WebhookSubscription::query()->where('store_id', app('current_store')->id)->whereKey($webhookId)->delete();
        $this->dispatch('toast', type: 'success', message: __('Webhook deleted'));
    }

    public function render()
    {
        return view('livewire.admin.developers.index');
    }
}
