<?php

namespace App\Livewire\Storefront\Pages;

use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public string $handle = '';

    public function render()
    {
        $store = app('current_store');

        $page = Page::where('store_id', $store->id)
            ->where('handle', $this->handle)
            ->published()
            ->firstOrFail();

        return view('livewire.storefront.pages.show', [
            'page' => $page,
        ])->title($page->title);
    }
}
