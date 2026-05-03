<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\SanitizeHtml;
use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListPagesRequest;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Http\Resources\Admin\PageResource;
use App\Models\Page;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class PageController extends Controller
{
    public function index(ListPagesRequest $request, Store $store): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 25);

        $query = Page::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($validated['query'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('title', 'like', '%'.$term.'%')
                        ->orWhere('handle', 'like', '%'.$term.'%');
                });
            })
            ->latest('updated_at');

        return PageResource::collection(
            $query->paginate($perPage)->appends($request->query())
        );
    }

    public function store(StorePageRequest $request, Store $store, HandleGenerator $handles, SanitizeHtml $sanitizeHtml): JsonResponse
    {
        $validated = $request->validated();
        $status = $validated['status'] ?? PageStatus::Draft->value;

        $page = Page::withoutGlobalScopes()->create([
            ...Arr::only($validated, ['title']),
            'store_id' => $store->id,
            'handle' => blank($validated['handle'] ?? null)
                ? $handles->generate($validated['title'], (new Page)->getTable(), $store->id)
                : $validated['handle'],
            'body_html' => $sanitizeHtml($validated['body_html'] ?? null),
            'status' => $status,
            'published_at' => $status === PageStatus::Published->value ? now() : null,
        ]);

        return (new PageResource($page->refresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdatePageRequest $request, Store $store, int $page, HandleGenerator $handles, SanitizeHtml $sanitizeHtml): PageResource
    {
        $existingPage = $this->findPage($store, $page);
        $validated = $request->validated();
        $payload = Arr::only($validated, ['title', 'handle', 'status']);

        if (array_key_exists('body_html', $validated)) {
            $payload['body_html'] = $sanitizeHtml($validated['body_html']);
        }

        if (array_key_exists('handle', $validated) && blank($validated['handle'])) {
            $payload['handle'] = $handles->generate($validated['title'] ?? $existingPage->title, $existingPage->getTable(), $store->id, $existingPage->id);
        }

        if (array_key_exists('status', $validated)) {
            $payload['published_at'] = $validated['status'] === PageStatus::Published->value
                ? ($existingPage->published_at ?? now())
                : null;
        }

        $existingPage->update($payload);

        return new PageResource($existingPage->refresh());
    }

    public function destroy(Store $store, int $page): JsonResponse
    {
        $this->findPage($store, $page)->delete();

        return response()->json(['message' => 'Page deleted']);
    }

    private function findPage(Store $store, int $pageId): Page
    {
        return Page::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereKey($pageId)
            ->firstOrFail();
    }
}
