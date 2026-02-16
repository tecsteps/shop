<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class Show extends Component
{
    public string $handle;

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function render(): mixed
    {
        $page = Page::query()
            ->where('handle', $this->handle)
            ->where('status', PageStatus::Published)
            ->first();

        if (! $page) {
            abort(404);
        }

        return view('livewire.storefront.pages.show', [
            'page' => $page,
        ]);
    }
}
