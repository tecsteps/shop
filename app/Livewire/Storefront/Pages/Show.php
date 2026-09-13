<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Livewire\Storefront\Concerns\ResolvesStore;
use App\Models\Page;
use Livewire\Component;

class Show extends Component
{
    use ResolvesStore;

    public string $handle = '';

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function render()
    {
        $store = $this->store();

        $page = Page::query()
            ->where('store_id', $store->getKey())
            ->where('handle', $this->handle)
            ->where('status', PageStatus::Published)
            ->first();

        abort_if($page === null, 404);

        return view('livewire.storefront.pages.show', [
            'store' => $store,
            'page' => $page,
        ])->layout('storefront.layouts.app', [
            'title' => $page->title.' - '.$store->name,
            'metaDescription' => mb_substr(trim(strip_tags((string) $page->body_html)) ?: $page->title, 0, 160),
        ]);
    }
}
