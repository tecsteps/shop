<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends StorefrontController
{
    public function show(Request $request, string $handle)
    {
        $page = Page::query()
            ->where('status', PageStatus::Published)
            ->where('handle', $handle)
            ->firstOrFail();

        return view('storefront.pages.show', $this->storefrontViewData($request, [
            'page' => $page,
            'title' => $page->title,
            'metaDescription' => strip_tags((string) $page->body_html),
        ]));
    }
}
