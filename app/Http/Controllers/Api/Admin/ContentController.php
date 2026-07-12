<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Theme;
use App\Services\SearchService;
use App\Support\HandleGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class ContentController extends Controller
{
    public function __construct(private readonly HandleGenerator $handles, private readonly SearchService $search) {}

    public function pages(Request $request, int $storeId): JsonResponse
    {
        $data = $request->validate(['status' => ['sometimes', 'in:draft,published,archived'], 'per_page' => ['sometimes', 'integer', 'max:100']]);
        $pages = Page::withoutGlobalScopes()->where('store_id', $storeId)->when(isset($data['status']), fn ($q) => $q->where('status', $data['status']))->paginate($data['per_page'] ?? 25);

        return response()->json(['data' => $pages->items(), 'meta' => ['total' => $pages->total()]]);
    }

    public function storePage(Request $request, int $storeId): JsonResponse
    {
        $data = $this->pageData($request, $storeId);
        $page = Page::withoutGlobalScopes()->create(['store_id' => $storeId, ...$data, 'handle' => $data['handle'] ?? $this->handles->generate($data['title'], 'pages', $storeId)]);

        return response()->json(['data' => $page], 201);
    }

    public function updatePage(Request $request, int $storeId, int $pageId): JsonResponse
    {
        $page = $this->page($storeId, $pageId);
        $page->update($this->pageData($request, $storeId, $pageId, true));

        return response()->json(['data' => $page->refresh()]);
    }

    public function destroyPage(int $storeId, int $pageId): JsonResponse
    {
        $this->page($storeId, $pageId)->delete();

        return response()->json(['message' => 'Page deleted.']);
    }

    public function storeTheme(Request $request, int $storeId): JsonResponse
    {
        $data = $request->validate(['name' => ['required_without:file', 'nullable', 'string'], 'file' => ['sometimes', 'file', 'mimes:zip', 'max:51200']]);
        $theme = Theme::withoutGlobalScopes()->create(['store_id' => $storeId, 'name' => $data['name'] ?? pathinfo($data['file']->getClientOriginalName(), PATHINFO_FILENAME), 'version' => '1.0.0', 'status' => 'draft']);
        if (isset($data['file'])) {
            $path = $data['file']->store("themes/{$storeId}/{$theme->id}", 'local');
            $theme->files()->create(['path' => 'theme.zip', 'storage_key' => $path, 'sha256' => hash_file('sha256', $data['file']->getRealPath()), 'byte_size' => $data['file']->getSize()]);
        }

        return response()->json(['data' => $theme->load('files')], 201);
    }

    public function publishTheme(int $storeId, int $themeId): JsonResponse
    {
        $theme = Theme::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($themeId);
        DB::transaction(function () use ($storeId, $theme): void {
            Theme::withoutGlobalScopes()->where('store_id', $storeId)->whereKeyNot($theme->id)->update(['status' => 'draft', 'published_at' => null]);
            $theme->update(['status' => 'published', 'published_at' => now()]);
        });

        return response()->json(['data' => $theme->refresh()]);
    }

    public function updateThemeSettings(Request $request, int $storeId, int $themeId): JsonResponse
    {
        $theme = Theme::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($themeId);
        $data = $request->validate(['settings' => ['required', 'array']]);
        $settings = $theme->settings()->updateOrCreate(['theme_id' => $theme->id], ['settings_json' => $data['settings']]);

        return response()->json(['data' => $settings]);
    }

    public function reindex(int $storeId): JsonResponse
    {
        \App\Models\Product::withoutGlobalScopes()->where('store_id', $storeId)->each(fn ($product) => $this->search->syncProduct($product));

        return response()->json(['message' => 'Search index rebuilt.'], 202);
    }

    public function searchStatus(int $storeId): JsonResponse
    {
        return response()->json(['data' => ['status' => 'ready', 'products_indexed' => \App\Models\Product::withoutGlobalScopes()->where('store_id', $storeId)->count(), 'last_indexed_at' => now()]]);
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
