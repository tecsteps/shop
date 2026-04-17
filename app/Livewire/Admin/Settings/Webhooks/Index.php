<?php

namespace App\Livewire\Admin\Settings\Webhooks;

use App\Models\WebhookSubscription;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public function render(): View
    {
        $subscriptions = WebhookSubscription::query()
            ->orderByDesc('id')
            ->get();

        return view('livewire.admin.settings.webhooks.index', [
            'subscriptions' => $subscriptions,
        ]);
    }
}
