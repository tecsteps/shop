<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Page;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
    public function __construct(private readonly HandleGenerator $handleGenerator) {}

    public function index(Store $store): JsonResponse
    {
        $this->ensureStore($store);

        return response()->json(['data' => Page::query()->latest()->paginate(15)]);
    }

    public function store(StorePageRequest $request, Store $store): JsonResponse
    {
        $this->ensureStore($store);
        $data = $request->safe()->only(['title', 'handle', 'body_html', 'status']);
        $data['store_id'] = $store->id;
        $data['handle'] = $this->handleGenerator->generate($data['handle'] ?? $data['title'], 'pages', $store->id);
        $page = Page::query()->create($data);

        return response()->json(['data' => $page], Response::HTTP_CREATED);
    }

    public function update(UpdatePageRequest $request, Store $store, Page $page): JsonResponse
    {
        $this->ensureRelated($store, $page);
        $data = $request->safe()->only(['title', 'handle', 'body_html', 'status']);

        if (isset($data['handle'])) {
            $data['handle'] = $this->handleGenerator->generate($data['handle'], 'pages', $store->id, $page->id);
        }

        $page->update($data);

        return response()->json(['data' => $page->refresh()]);
    }

    public function destroy(Store $store, Page $page): JsonResponse
    {
        $this->ensureRelated($store, $page);
        $page->delete();

        return response()->json(['deleted' => true]);
    }

    private function ensureStore(Store $store): void
    {
        abort_unless($store->is(app('current_store')), Response::HTTP_NOT_FOUND);
    }

    private function ensureRelated(Store $store, Page $page): void
    {
        $this->ensureStore($store);
        abort_unless($page->store_id === $store->id, Response::HTTP_NOT_FOUND);
    }
}
