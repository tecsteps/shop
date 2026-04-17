<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public string $handle = '';

    public string $title = '';

    public string $bodyHtml = '';

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        $page = Page::query()
            ->where('handle', $handle)
            ->where('status', PageStatus::Published)
            ->first();

        if (! $page) {
            abort(404);
        }

        $this->title = $page->title;
        $this->bodyHtml = $page->body_html ?? '';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.pages.show');
    }
}
