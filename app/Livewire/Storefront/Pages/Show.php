<?php

namespace App\Livewire\Storefront\Pages;

use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::storefront')]
class Show extends Component
{
    public Page $page;

    public function mount(string $handle): void
    {
        $this->page = Page::query()
            ->published()
            ->where('handle', $handle)
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.storefront.pages.show')
            ->layout('layouts::storefront', [
                'metaDescription' => Str::limit(trim(strip_tags((string) $this->page->body_html)), 160, ''),
            ])
            ->title($this->page->title);
    }
}
