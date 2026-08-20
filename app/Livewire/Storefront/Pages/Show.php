<?php

namespace App\Livewire\Storefront\Pages;

use App\Models\Page;
use Livewire\Component;

class Show extends Component
{
    public Page $page;

    public function mount(string $handle): void
    {
        $this->page = Page::query()->where('handle', $handle)->where('status', 'published')->firstOrFail();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.pages.show')->layout('layouts.storefront');
    }
}
