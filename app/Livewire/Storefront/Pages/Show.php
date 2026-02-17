<?php

namespace App\Livewire\Storefront\Pages;

use App\Enums\PageStatus;
use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public string $handle = '';

    public function mount(string $handle): void
    {
        $this->handle = $handle;
    }

    public function render(): \Illuminate\View\View
    {
        $page = null;

        if (app()->bound('current_store')) {
            $page = Page::withoutGlobalScopes()
                ->where('store_id', app('current_store')->id)
                ->where('handle', $this->handle)
                ->where('status', PageStatus::Published)
                ->first();
        }

        if (! $page) {
            abort(404);
        }

        $storeName = app()->bound('current_store') ? app('current_store')->name : config('app.name');

        return view('livewire.storefront.pages.show', [
            'page' => $page,
        ])->title("{$page->title} - {$storeName}");
    }
}
