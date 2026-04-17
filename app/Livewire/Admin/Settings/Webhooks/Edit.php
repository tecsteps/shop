<?php

namespace App\Livewire\Admin\Settings\Webhooks;

use App\Enums\WebhookTopic;
use App\Models\WebhookSubscription;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    public ?int $subscriptionId = null;

    public string $event_type = '';

    public string $target_url = '';

    public string $signing_secret = '';

    public string $status = 'active';

    public function mount(?int $subscription = null): void
    {
        if ($subscription !== null) {
            $row = WebhookSubscription::query()->findOrFail($subscription);
            $this->subscriptionId = (int) $row->getKey();
            $this->event_type = (string) $row->event_type;
            $this->target_url = (string) $row->target_url;
            $this->signing_secret = (string) $row->signing_secret_encrypted;
            $this->status = (string) $row->status;
        } else {
            $this->event_type = WebhookTopic::OrderPaid->value;
            $this->signing_secret = Str::random(32);
        }
    }

    public function save(): mixed
    {
        $this->validate([
            'event_type' => 'required|string|in:'.implode(',', WebhookTopic::values()),
            'target_url' => 'required|url',
            'signing_secret' => 'required|string|min:8',
            'status' => 'required|in:active,paused,disabled',
        ]);

        $store = app('current_store');

        $attributes = [
            'store_id' => $store->getKey(),
            'event_type' => $this->event_type,
            'target_url' => $this->target_url,
            'signing_secret_encrypted' => $this->signing_secret,
            'status' => $this->status,
            'created_at' => now(),
        ];

        if ($this->subscriptionId === null) {
            WebhookSubscription::query()->create($attributes);
        } else {
            WebhookSubscription::query()->findOrFail($this->subscriptionId)->update($attributes);
        }

        return redirect('/admin/settings/webhooks');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.webhooks.edit', [
            'topics' => WebhookTopic::cases(),
        ]);
    }
}
