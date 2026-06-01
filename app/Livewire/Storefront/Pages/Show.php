<?php

namespace App\Livewire\Storefront\Pages;

use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Renders a published CMS page (About, Contact, etc.) by its handle.
 */
#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public Page $page;

    /**
     * Resolve the page by handle within the current store, 404 unless published.
     */
    public function mount(string $handle): void
    {
        $this->page = Page::query()
            ->where('handle', $handle)
            ->published()
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.storefront.pages.show')
            ->title($this->page->title);
    }
}
