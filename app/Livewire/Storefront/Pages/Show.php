<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public string $handle = '';

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function render(): View
    {
        $page = Page::query()
            ->where('handle', $this->handle)
            ->where('status', PageStatus::Published)
            ->firstOrFail();

        return view('livewire.storefront.pages.show', [
            'page' => $page,
        ])->layout('storefront.layouts.app', [
            'title' => $page->title,
        ]);
    }
}
