<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public ?Page $page = null;

    public function mount(string $handle): void
    {
        $this->page = Page::query()
            ->where('handle', $handle)
            ->where('status', PageStatus::Published)
            ->first();

        if (! $this->page) {
            abort(404);
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.storefront.pages.show');
    }
}
