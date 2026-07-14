<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Jobs\ReindexProducts;
use App\Models\Page;
use App\Models\StoreSettings;
use App\Models\Theme;
use App\Services\ThemeArchiveService;
use App\Support\HandleGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class ContentController extends Controller
{
    public function __construct(
        private readonly HandleGenerator $handles,
        private readonly ThemeArchiveService $themeArchives,
    ) {}

    public function pages(Request $request, int $storeId): JsonResponse
    {
        $this->authorize('viewAny', Page::class);
        $data = $request->validate(['status' => ['sometimes', 'in:draft,published,archived'], 'per_page' => ['sometimes', 'integer', 'max:100']]);
        $pages = Page::withoutGlobalScopes()->where('store_id', $storeId)->when(isset($data['status']), fn ($q) => $q->where('status', $data['status']))->paginate($data['per_page'] ?? 25);

        return response()->json(['data' => $pages->items(), 'meta' => ['total' => $pages->total()]]);
    }

    public function storePage(StorePageRequest $request, int $storeId): JsonResponse
    {
        $this->authorize('create', Page::class);
        $data = $this->pageData($request, $storeId);
        $page = Page::withoutGlobalScopes()->create(['store_id' => $storeId, ...$data, 'handle' => $data['handle'] ?? $this->handles->generate($data['title'], 'pages', $storeId)]);

        return response()->json(['data' => $page], 201);
    }

    public function updatePage(UpdatePageRequest $request, int $storeId, int $pageId): JsonResponse
    {
        $page = $this->page($storeId, $pageId);
        $this->authorize('update', $page);
        $page->update($this->pageData($request, $storeId, $pageId, true));

        return response()->json(['data' => $page->refresh()]);
    }

    public function destroyPage(int $storeId, int $pageId): JsonResponse
    {
        $page = $this->page($storeId, $pageId);
        $this->authorize('delete', $page);
        $page->delete();

        return response()->json(['message' => 'Page deleted.']);
    }

    public function storeTheme(Request $request, int $storeId): JsonResponse
    {
        $this->authorize('create', Theme::class);
        $data = $request->validate(['name' => ['nullable', 'string', 'max:255'], 'file' => ['required', 'file', 'mimes:zip', 'max:51200']]);
        $theme = $this->themeArchives->install(app('current_store'), $data['file'], $data['name'] ?? null);

        return response()->json(['data' => $theme], 201);
    }

    public function publishTheme(int $storeId, int $themeId): JsonResponse
    {
        $theme = Theme::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($themeId);
        $this->authorize('publish', $theme);
        DB::transaction(function () use ($storeId, $theme): void {
            Theme::withoutGlobalScopes()->where('store_id', $storeId)->whereKeyNot($theme->id)->update(['status' => 'draft', 'published_at' => null]);
            $theme->update(['status' => 'published', 'published_at' => now()]);
        });

        return response()->json(['data' => $theme->refresh()]);
    }

    public function updateThemeSettings(Request $request, int $storeId, int $themeId): JsonResponse
    {
        $theme = Theme::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($themeId);
        $this->authorize('update', $theme);
        $data = $request->validate(['settings' => ['required', 'array']]);
        $settings = $theme->settings()->updateOrCreate(['theme_id' => $theme->id], ['settings_json' => $data['settings']]);

        return response()->json(['data' => $settings]);
    }

    public function reindex(int $storeId): JsonResponse
    {
        $this->authorize('update', app('current_store'));
        $jobId = DB::transaction(function () use ($storeId): string {
            $record = StoreSettings::withoutGlobalScopes()
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->first() ?? new StoreSettings(['store_id' => $storeId]);
            $settings = (array) $record->settings_json;
            abort_if(
                in_array(data_get($settings, 'search.status'), ['queued', 'processing'], true),
                409,
                'A search reindex is already in progress.',
            );

            $jobId = 'job_reindex_'.Str::lower(Str::random(12));
            data_set($settings, 'search.status', 'queued');
            data_set($settings, 'search.progress', 0);
            data_set($settings, 'search.job_id', $jobId);
            $record->settings_json = $settings;
            $record->save();

            return $jobId;
        });
        ReindexProducts::dispatch($storeId)->afterCommit();

        return response()->json([
            'message' => 'Reindex job queued.',
            'job_id' => $jobId,
            'status' => 'queued',
        ], 202);
    }

    public function searchStatus(int $storeId): JsonResponse
    {
        $this->authorize('view', app('current_store'));
        $record = StoreSettings::withoutGlobalScopes()->where('store_id', $storeId)->first();
        $search = (array) data_get($record?->settings_json, 'search', []);

        return response()->json(['data' => [
            'store_id' => $storeId,
            'index_status' => $search['status'] ?? 'never_indexed',
            'last_reindex_at' => $search['last_reindex_at'] ?? $search['last_indexed_at'] ?? null,
            'last_reindex_duration_seconds' => $search['last_reindex_duration_seconds'] ?? null,
            'documents_count' => (int) ($search['documents_count'] ?? $search['products_indexed'] ?? 0),
            'pending_updates' => (int) ($search['pending_updates'] ?? 0),
        ]]);
    }

    /** @return array<string, mixed> */
    private function pageData(Request $request, int $storeId, ?int $ignore = null, bool $partial = false): array
    {
        return $request->validate([
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'handle' => ['sometimes', 'string', Rule::unique('pages')->where('store_id', $storeId)->ignore($ignore)],
            'body_html' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    private function page(int $storeId, int $id): Page
    {
        return Page::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($id);
    }
}
