<?php

namespace App\Livewire\Storefront\Concerns;

trait DispatchesStorefrontAnalytics
{
    /** @param array<string, mixed> $properties */
    protected function dispatchStorefrontAnalytics(string $eventType, array $properties = []): void
    {
        $this->dispatch('storefront-analytics', type: $eventType, properties: $properties);
    }
}
