<?php

namespace App\Livewire\Admin\Developers;

use App\Models\Store;
use App\Models\User;
use App\Models\WebhookSubscription;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    #[Validate('required|string|max:255')]
    public string $newTokenName = '';

    public ?string $plaintextToken = null;

    #[Validate('required|string|max:255')]
    public string $webhookEventType = '';

    #[Validate('required|string|max:2048')]
    public string $webhookUrl = '';

    public function createToken(): void
    {
        $this->validateOnly('newTokenName');

        /** @var User $user */
        $user = Auth::user();

        $token = $user->createToken($this->newTokenName);
        $this->plaintextToken = $token->plainTextToken;
        $this->newTokenName = '';

        session()->flash('status', 'API token created. Copy it now; it will not be shown again.');
    }

    public function revokeToken(int $tokenId): void
    {
        /** @var User $user */
        $user = Auth::user();

        $user->tokens()->where('id', $tokenId)->delete();

        session()->flash('status', 'Token revoked.');
    }

    public function createWebhook(): void
    {
        $this->validate([
            'webhookEventType' => 'required|string|max:255',
            'webhookUrl' => 'required|string|max:2048',
        ]);

        /** @var Store $store */
        $store = app('current_store');

        WebhookSubscription::create([
            'store_id' => $store->id,
            'event_type' => $this->webhookEventType,
            'url' => $this->webhookUrl,
            'secret' => Str::random(40),
            'status' => 'active',
            'failed_count' => 0,
        ]);

        $this->reset('webhookEventType', 'webhookUrl');
        session()->flash('status', 'Webhook subscription created.');
    }

    public function deleteWebhook(int $webhookId): void
    {
        $webhook = WebhookSubscription::query()->findOrFail($webhookId);
        $webhook->delete();

        session()->flash('status', 'Webhook deleted.');
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $tokens = $user->tokens()->orderByDesc('created_at')->get();

        $webhooks = WebhookSubscription::query()
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.admin.developers.index', [
            'tokens' => $tokens,
            'webhooks' => $webhooks,
        ]);
    }
}
