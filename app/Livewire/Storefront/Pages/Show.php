<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
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

    public function render(): View
    {
        return view('livewire.storefront.pages.show');
    }
}
