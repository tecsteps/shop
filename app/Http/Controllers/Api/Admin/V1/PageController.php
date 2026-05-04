<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Enums\PageStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\PageResource;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Store;
use App\Services\NavigationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class PageController extends Controller
{
    public function index(Request $request, Store $store): AnonymousResourceCollection
    {
        $this->authorizeStore($request, $store);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in($this->pageStatusValues())],
            'query' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $pages = Page::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->when(data_get($validated, 'status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when(data_get($validated, 'query'), function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('handle', 'like', '%'.$search.'%');
                });
            })
            ->latest('updated_at')
            ->latest('id')
            ->paginate((int) data_get($validated, 'per_page', 25));

        return PageResource::collection($pages);
    }

    public function store(Request $request, Store $store, NavigationService $navigation): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $validated = $this->validatePayload($request, $store);

        $page = Page::withoutGlobalScopes()->create([
            'store_id' => $store->getKey(),
            ...$this->attributesForCreate($validated),
        ]);

        $this->forgetNavigation($store, $navigation);

        return PageResource::make($page)
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Store $store, Page $page, NavigationService $navigation): PageResource
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessPageBelongsToStore($page, $store);

        $validated = $this->validatePayload($request, $store, $page);
        $attributes = $this->attributesForUpdate($validated, $page);

        if ($attributes !== []) {
            $page->update($attributes);
            $this->forgetNavigation($store, $navigation);
        }

        return PageResource::make($page->refresh());
    }

    public function destroy(Request $request, Store $store, Page $page, NavigationService $navigation): JsonResponse
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessPageBelongsToStore($page, $store);

        $page->delete();
        $this->forgetNavigation($store, $navigation);

        return response()->json(['message' => 'Page deleted']);
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('admin_api_oauth_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function abortUnlessPageBelongsToStore(Page $page, Store $store): void
    {
        abort_unless((int) $page->store_id === $store->getKey(), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, Store $store, ?Page $page = null): array
    {
        $creating = $page === null;

        $validator = Validator::make($request->all(), [
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'body_html' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'status' => ['sometimes', Rule::in($this->pageStatusValues())],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($request, $store, $page, $creating): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $handle = $this->handleForRequest($request, $page, $creating);

            if ($handle === '') {
                $validator->errors()->add('handle', __('The handle must contain at least one letter or number.'));

                return;
            }

            $exists = Page::withoutGlobalScopes()
                ->where('store_id', $store->getKey())
                ->where('handle', $handle)
                ->when($page instanceof Page, fn (Builder $query) => $query->whereKeyNot($page->getKey()))
                ->exists();

            if ($exists) {
                $validator->errors()->add('handle', __('The handle has already been taken.'));
            }
        });

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesForCreate(array $validated): array
    {
        $status = PageStatus::from(data_get($validated, 'status', PageStatus::Draft->value));

        return [
            'title' => $validated['title'],
            'handle' => $this->normalizeHandle($validated['handle'] ?? $validated['title']),
            'body_html' => $validated['body_html'] ?? null,
            'status' => $status,
            'published_at' => $this->publishedAtForCreate($validated, $status),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesForUpdate(array $validated, Page $page): array
    {
        $attributes = Arr::only($validated, ['title', 'body_html']);

        if (array_key_exists('handle', $validated) && filled($validated['handle'])) {
            $attributes['handle'] = $this->normalizeHandle($validated['handle']);
        }

        if (array_key_exists('status', $validated)) {
            $attributes['status'] = PageStatus::from($validated['status']);
        }

        if (array_key_exists('published_at', $validated)) {
            $attributes['published_at'] = $validated['published_at'];
        } elseif (($attributes['status'] ?? $page->status) === PageStatus::Published && $page->published_at === null) {
            $attributes['published_at'] = now();
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function publishedAtForCreate(array $validated, PageStatus $status): mixed
    {
        if (array_key_exists('published_at', $validated)) {
            return $validated['published_at'];
        }

        return $status === PageStatus::Published ? now() : null;
    }

    private function handleForRequest(Request $request, ?Page $page, bool $creating): string
    {
        if ($request->exists('handle') && filled($request->input('handle'))) {
            return $this->normalizeHandle($request->input('handle'));
        }

        if ($creating) {
            return $this->normalizeHandle($request->input('title'));
        }

        return (string) $page?->handle;
    }

    private function normalizeHandle(mixed $value): string
    {
        return Str::slug((string) $value);
    }

    /**
     * @return list<string>
     */
    private function pageStatusValues(): array
    {
        return array_map(fn (PageStatus $status): string => $status->value, PageStatus::cases());
    }

    private function forgetNavigation(Store $store, NavigationService $navigation): void
    {
        NavigationMenu::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->get()
            ->each(fn (NavigationMenu $menu): mixed => $navigation->forget($menu));
    }
}
