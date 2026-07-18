<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Livewire\Component;

class Show extends Component
{
    public Page $page;

    public function mount(string $handle): void
    {
        $this->page = Page::query()
            ->where('handle', $handle)
            ->where('status', PageStatus::Published)
            ->firstOr(fn () => abort(404));
    }

    public function render()
    {
        return view('livewire.storefront.pages.show')
            ->layout('layouts.storefront')
            ->title($this->page->title.' - '.app('current_store')->name);
    }
}
