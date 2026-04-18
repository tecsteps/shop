<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Layout('components.layouts.storefront')]
class Show extends Component
{
    public string $handle;

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function render()
    {
        $page = Page::query()
            ->where('handle', $this->handle)
            ->where('status', PageStatus::Published->value)
            ->first();

        if (! $page) {
            throw new NotFoundHttpException('Page not found');
        }

        return view('livewire.storefront.pages.show', [
            'page' => $page,
        ]);
    }
}
