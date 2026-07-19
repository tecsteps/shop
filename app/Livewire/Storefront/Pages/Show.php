<?php

namespace App\Livewire\Storefront\Pages;

use App\Models\Page;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public Page $page;

    /**
     * Resolve the page by handle; only published pages are visible.
     */
    public function mount(string $handle): void
    {
        $this->page = Page::query()
            ->published()
            ->where('handle', $handle)
            ->firstOrFail();
    }

    /**
     * Render the content page.
     */
    public function render(): View
    {
        $storeName = app('current_store')->name;

        return view('livewire.storefront.pages.show')
            ->layout('storefront.layouts.app', [
                'metaDescription' => str()->limit(trim(strip_tags($this->page->body_html ?? '')), 160, ''),
            ])
            ->title("{$this->page->title} - {$storeName}");
    }
}
