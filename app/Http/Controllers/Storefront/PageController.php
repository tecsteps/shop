<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PageController extends StorefrontController
{
    public function show(Request $request, string $handle): View
    {
        $page = Page::query()
            ->where('store_id', $this->currentStoreId($request))
            ->where('handle', $handle)
            ->where('status', PageStatus::Published->value)
            ->where(function ($query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->firstOrFail();

        return view('storefront.pages.show', [
            'page' => $page,
        ]);
    }
}
