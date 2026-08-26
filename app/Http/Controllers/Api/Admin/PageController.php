<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $request, int $storeId)
    {
        $this->authorize('viewAny', Page::class);

        $pages = Page::query()->paginate($request->per_page ?? 25);

        return response()->json([
            'data' => $pages->map(fn (Page $page) => [
                'id' => $page->id,
                'title' => $page->title,
                'handle' => $page->handle,
                'status' => $page->status,
            ]),
            'meta' => [
                'current_page' => $pages->currentPage(),
                'per_page' => $pages->perPage(),
                'total' => $pages->total(),
                'last_page' => $pages->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, int $storeId)
    {
        $this->authorize('create', Page::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'body_html' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:draft,published'],
        ]);

        $page = Page::create([
            'store_id' => app('current_store')->id,
            'title' => $validated['title'],
            'handle' => $validated['handle'] ?? Str::slug($validated['title']),
            'body_html' => $validated['body_html'] ?? null,
            'status' => $validated['status'] ?? 'draft',
            'published_at' => ($validated['status'] ?? 'draft') === 'published' ? now() : null,
        ]);

        return response()->json(['data' => ['id' => $page->id, 'title' => $page->title]], 201);
    }
}
