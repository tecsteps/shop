<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public string $handle = '';

    public ?Page $page = null;

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        $page = Page::query()
            ->where('handle', $handle)
            ->first();

        if ($page === null || $page->status !== PageStatus::Published) {
            abort(404);
        }

        $this->page = $page;
    }

    public function render(): View
    {
        return view('livewire.storefront.pages.show');
    }
}
