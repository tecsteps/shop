<?php

namespace App\Livewire\Admin\Developers;

use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public string $tokenName = '';

    public string $abilities = 'read';

    public ?string $newToken = null;

    public function createToken(): void
    {
        $this->validate([
            'tokenName' => 'required|string|max:100',
            'abilities' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $abilitiesArr = array_values(array_filter(array_map('trim', explode(',', $this->abilities)))) ?: ['*'];
        $token = $user->createToken($this->tokenName, $abilitiesArr);
        $this->newToken = $token->plainTextToken;
        $this->tokenName = '';
    }

    public function revokeToken(int $id): void
    {
        $user = auth()->user();
        $user->tokens()->whereKey($id)->delete();
    }

    public function render()
    {
        $user = auth()->user();
        $tokens = $user->tokens()->orderByDesc('id')->get();

        $subscriptions = collect();
        if (class_exists(\App\Models\WebhookSubscription::class) && Schema::hasTable('webhook_subscriptions')) {
            $store = app('current_store');
            $subscriptions = \App\Models\WebhookSubscription::query()
                ->where('store_id', $store->id)
                ->get();
        }

        return view('livewire.admin.developers.index', [
            'tokens' => $tokens,
            'subscriptions' => $subscriptions,
        ]);
    }
}
