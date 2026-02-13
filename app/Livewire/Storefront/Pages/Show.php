<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public Page $page;

    public function mount(string $handle): void
    {
        $this->page = Page::query()
            ->where('handle', $handle)
            ->where('status', PageStatus::Published)
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.storefront.pages.show')
            ->layout('storefront.layouts.app', [
                'title' => $this->page->title,
            ]);
    }
}
