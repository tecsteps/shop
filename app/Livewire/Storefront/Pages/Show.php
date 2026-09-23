<?php

namespace App\Livewire\Storefront\Pages;

use App\Models\Page;
use App\Support\HtmlSanitizer;
use Livewire\Component;

class Show extends Component
{
    public function render(): mixed
    {
        $page = Page::query()
            ->where('handle', request()->route('handle'))
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->firstOrFail();

        $safeHtml = app(HtmlSanitizer::class)->sanitize($page->body_html);

        return view('storefront.page', ['page' => $page, 'safeHtml' => $safeHtml])->layout('storefront.layouts.app');
    }
}
