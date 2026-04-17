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
        $page = Page::query()
            ->where('handle', $handle)
            ->where('status', PageStatus::Published)
            ->first();

        if (! $page) {
            abort(404);
        }

        $this->page = $page;
    }

    public function render(): mixed
    {
        return view('livewire.storefront.pages.show')
            ->layout('layouts.storefront', ['title' => $this->page->title]);
    }
}
