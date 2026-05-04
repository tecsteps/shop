<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Livewire\Component;

class Show extends Component
{
    public string $handle;

    public string $title;

    public string $bodyHtml;

    public function mount(string $handle): void
    {
        $page = Page::query()
            ->where('handle', $handle)
            ->where('status', PageStatus::Published)
            ->whereNotNull('published_at')
            ->firstOrFail();

        $this->handle = $page->handle;
        $this->title = $page->title;
        $this->bodyHtml = (string) $page->body_html;
    }

    public function render(): mixed
    {
        return view('livewire.storefront.pages.show')
            ->layout('layouts.storefront', [
                'title' => $this->title,
            ]);
    }
}
