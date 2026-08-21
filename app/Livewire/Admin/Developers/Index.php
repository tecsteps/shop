<?php

namespace App\Livewire\Admin\Developers;

use App\Models\WebhookSubscription;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class Index extends Component
{
    public string $event = 'order.created';

    public string $targetUrl = '';

    public string $tokenName = '';

    public string $tokenExpiresAt = '';

    public string $tokenAbilities = 'read-products,read-orders';

    public ?string $plainTextToken = null;

    public function createWebhook(): void
    {
        abort_unless(auth()->user()?->canManageStore(app('current_store')), 403);
        $data = $this->validate(['event' => ['required', 'string', 'max:100'], 'targetUrl' => ['required', 'url', 'max:2000']]);
        WebhookSubscription::query()->create(['event' => $data['event'], 'event_type' => $data['event'], 'target_url' => $data['targetUrl'], 'signing_secret_encrypted' => Str::random(48), 'status' => 'active']);
        $this->reset('targetUrl');
    }

    public function pause(int $subscriptionId): void
    {
        abort_unless(auth()->user()?->canManageStore(app('current_store')), 403);
        $subscription = WebhookSubscription::query()->findOrFail($subscriptionId);
        $subscription->update(['status' => $subscription->status === 'active' ? 'paused' : 'active']);
    }

    public function createToken(): void
    {
        abort_unless(auth()->user()?->canManageStore(app('current_store')), 403);
        $data = $this->validate([
            'tokenName' => ['required', 'string', 'max:100'],
            'tokenExpiresAt' => ['nullable', 'date', 'after:today'],
            'tokenAbilities' => ['required', 'string', 'max:1000'],
        ]);
        $allowed = ['read-products', 'write-products', 'read-orders', 'write-orders', 'read-customers', 'write-customers', 'read-collections', 'write-collections', 'read-discounts', 'write-discounts', 'read-analytics', 'read-settings', 'write-settings', 'read-themes', 'write-themes', 'read-content', 'write-content'];

        if (auth()->user()?->isPlatformAdmin()) {
            $allowed[] = 'manage-platform';
        }
        $abilities = array_values(array_intersect($allowed, array_filter(array_map('trim', explode(',', $data['tokenAbilities'])))));
        abort_if($abilities === [], 422, 'Select at least one token ability.');
        $expiresAt = empty($data['tokenExpiresAt']) ? null : CarbonImmutable::parse($data['tokenExpiresAt']);
        $this->plainTextToken = auth()->user()->createToken($data['tokenName'], $abilities, $expiresAt)->plainTextToken;
        $this->reset(['tokenName', 'tokenExpiresAt']);
    }

    public function revokeToken(int $tokenId): void
    {
        abort_unless(auth()->user()?->canManageStore(app('current_store')), 403);
        auth()->user()->tokens()->whereKey($tokenId)->delete();
    }

    public function render(): View
    {
        return view('livewire.admin.developers.index', ['subscriptions' => WebhookSubscription::query()->latest()->get(), 'tokens' => auth()->user()->tokens()->latest()->get()])->layout('layouts.admin');
    }
}
