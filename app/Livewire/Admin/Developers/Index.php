<?php

namespace App\Livewire\Admin\Developers;

use App\Enums\StoreUserRole;
use App\Livewire\Admin\AdminComponent;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Index extends AdminComponent
{
    public string $tokenName = '';

    public ?string $newToken = null;

    public string $webhookEvent = 'order.created';

    public string $webhookUrl = '';

    public function mount(): void
    {
        $this->authorizeStore([StoreUserRole::Owner, StoreUserRole::Admin]);
    }

    public function createToken(): void
    {
        $validated = $this->validate(['tokenName' => ['required', 'string', 'max:100']]);
        $this->newToken = auth()->user()->createToken($validated['tokenName'], ['store:'.$this->currentStore()->getKey()])->plainTextToken;
        $this->reset('tokenName');
        $this->toast('API token created. Copy it now.');
    }

    public function revokeToken(int $id): void
    {
        auth()->user()->tokens()->findOrFail($id)->delete();
        $this->toast('API token revoked.');
    }

    public function createWebhook(): void
    {
        $validated = $this->validate(['webhookEvent' => ['required', 'string', 'max:100'], 'webhookUrl' => ['required', 'url', 'starts_with:https://']]);
        WebhookSubscription::create(['store_id' => $this->currentStore()->getKey(), 'event_type' => $validated['webhookEvent'], 'target_url' => $validated['webhookUrl'], 'signing_secret_encrypted' => Str::random(64), 'status' => 'active']);
        $this->reset('webhookUrl');
        $this->toast('Webhook created.');
    }

    public function deleteWebhook(int $id): void
    {
        WebhookSubscription::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($id)->delete();
        $this->toast('Webhook deleted.');
    }

    #[Computed]
    public function tokens()
    {
        return auth()->user()->tokens()->latest()->get();
    }

    #[Computed]
    public function webhooks()
    {
        return WebhookSubscription::query()->where('store_id', $this->currentStore()->getKey())->latest('id')->get();
    }

    public function render()
    {
        return view('livewire.admin.developers.index');
    }
}
