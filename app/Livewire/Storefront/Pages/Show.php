<?php

namespace App\Livewire\Storefront\Pages;

use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public Page $page;

    public function mount(string $handle): void
    {
        $this->page = Page::query()->published()->where('handle', $handle)->firstOrFail();
    }

    public function render()
    {
        return view('livewire.storefront.pages.show')->title($this->page->title);
    }
}
