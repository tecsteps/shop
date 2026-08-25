<?php

namespace App\Livewire\Storefront\Pages;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Page;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    use InteractsWithStore;

    public string $handle = '';

    public function mount(string $handle): void
    {
        $this->handle = $handle;

        View::share([
            'title' => $this->page->title.' - '.$this->store()->name,
            'metaDescription' => mb_substr(strip_tags((string) $this->page->body_html), 0, 160),
        ]);
    }

    #[Computed]
    public function page(): Page
    {
        return Page::where('handle', $this->handle)
            ->where('status', 'published')
            ->firstOrFail();
    }
}
