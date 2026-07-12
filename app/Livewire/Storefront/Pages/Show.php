<?php

namespace App\Livewire\Storefront\Pages;

use App\Livewire\Storefront\StorefrontComponent;
use App\Models\Page;
use Illuminate\View\View;

class Show extends StorefrontComponent
{
    public Page $page;

    public function mount(string|Page $handle): void
    {
        $this->page = ($handle instanceof Page
            ? Page::query()->whereKey($handle->getKey())
            : Page::query()->where('handle', $handle))
            ->where('store_id', $this->currentStore()->getKey())
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->firstOrFail();
    }

    public function render(): View
    {
        return $this->storefront(
            view('storefront.pages.show'),
            $this->page->title.' - '.$this->currentStore()->name,
            str(strip_tags((string) $this->page->body_html))->squish()->limit(160)->toString(),
        );
    }
}
