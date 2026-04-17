<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Livewire\Storefront\Concerns\EnsuresStore;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    use EnsuresStore;

    public string $handle = '';

    public function mount(string $handle): void
    {
        $this->ensureCurrentStore();
        $this->handle = $handle;
    }

    public function render(): View
    {
        $page = Page::query()
            ->where('handle', $this->handle)
            ->where('status', PageStatus::Published->value)
            ->firstOrFail();

        return view('livewire.storefront.pages.show', [
            'page' => $page,
        ]);
    }
}
