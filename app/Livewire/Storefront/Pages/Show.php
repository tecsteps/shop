<?php

namespace App\Livewire\Storefront\Pages;

use Illuminate\Support\Str;
use Livewire\Component;

class Show extends Component
{
    public string $handle;

    public string $title;

    public function mount(string $handle): void
    {
        abort_unless(in_array($handle, ['about', 'faq'], true), 404);

        $this->handle = $handle;
        $this->title = Str::headline($handle);
    }

    public function render(): mixed
    {
        return view('livewire.storefront.pages.show')
            ->layout('layouts.storefront', [
                'title' => $this->title,
            ]);
    }
}
