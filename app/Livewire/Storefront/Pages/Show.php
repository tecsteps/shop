<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Livewire\Component;

class Show extends Component
{
    public string $handle = '';

    public ?Page $page = null;

    public function mount(string $handle): void
    {
        $this->handle = $handle;
        $this->page = Page::where('handle', $handle)
            ->where('status', PageStatus::Published)
            ->firstOrFail();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.storefront.pages.show')
            ->layout('layouts.storefront.app', [
                'title' => $this->page->title,
            ]);
    }
}
