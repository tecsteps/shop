<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NavigationController extends AdminController
{
    public function index(): View
    {
        $menu = $this->mainMenu();

        $items = $menu->items()
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return view('admin.navigation.index', [
            'menu' => $menu,
            'items' => $items,
            'types' => NavigationItemType::cases(),
            'nextPosition' => $items->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $menu = $this->mainMenu();
        $validated = $this->validateItem($request);

        $menu->items()->create($this->itemPayload($validated));

        return redirect()->route('admin.navigation.index')
            ->with('status', 'Navigation item added.');
    }

    public function update(Request $request, NavigationItem $item): RedirectResponse
    {
        $menu = $this->mainMenu();
        $this->ensureItemBelongsToMenu($item, $menu);

        $validated = $this->validateItem($request);

        $item->update($this->itemPayload($validated));

        return redirect()->route('admin.navigation.index')
            ->with('status', 'Navigation item updated.');
    }

    public function destroy(NavigationItem $item): RedirectResponse
    {
        $menu = $this->mainMenu();
        $this->ensureItemBelongsToMenu($item, $menu);

        $item->delete();

        return redirect()->route('admin.navigation.index')
            ->with('status', 'Navigation item removed.');
    }

    protected function mainMenu(): NavigationMenu
    {
        return NavigationMenu::query()->firstOrCreate(
            ['handle' => 'main-menu'],
            ['title' => 'Main Menu'],
        );
    }

    protected function ensureItemBelongsToMenu(NavigationItem $item, NavigationMenu $menu): void
    {
        if ($item->menu_id !== $menu->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateItem(Request $request): array
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in($this->enumValues(NavigationItemType::class))],
            'url' => ['nullable', 'string', 'max:255'],
            'resource_id' => ['nullable', 'integer', 'min:1'],
            'position' => ['required', 'integer', 'min:0'],
        ]);

        $isLink = $validated['type'] === NavigationItemType::Link->value;

        if ($isLink && trim((string) $validated['url']) === '') {
            throw ValidationException::withMessages([
                'url' => 'A URL is required for link navigation items.',
            ]);
        }

        if (! $isLink && $validated['resource_id'] === null && trim((string) $validated['url']) === '') {
            throw ValidationException::withMessages([
                'resource_id' => 'Provide a resource ID or a URL for non-link navigation items.',
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function itemPayload(array $validated): array
    {
        return [
            'type' => $validated['type'],
            'label' => $validated['label'],
            'url' => $this->resolveUrl(
                (string) $validated['type'],
                isset($validated['resource_id']) ? (int) $validated['resource_id'] : null,
                $validated['url']
            ),
            'resource_id' => $validated['type'] === NavigationItemType::Link->value
                ? null
                : (isset($validated['resource_id']) ? (int) $validated['resource_id'] : null),
            'position' => (int) $validated['position'],
        ];
    }

    protected function resolveUrl(string $type, ?int $resourceId, ?string $fallbackUrl): string
    {
        $fallback = trim((string) $fallbackUrl);

        if ($type === NavigationItemType::Link->value) {
            return $fallback !== '' ? $fallback : '/';
        }

        $resolved = null;

        if ($resourceId !== null) {
            $resolved = match ($type) {
                NavigationItemType::Page->value => $this->resourceHandleToUrl(Page::class, $resourceId, '/pages/%s'),
                NavigationItemType::Collection->value => $this->resourceHandleToUrl(Collection::class, $resourceId, '/collections/%s'),
                NavigationItemType::Product->value => $this->resourceHandleToUrl(Product::class, $resourceId, '/products/%s'),
                default => null,
            };
        }

        if ($resolved !== null) {
            return $resolved;
        }

        return $fallback !== '' ? $fallback : '/';
    }

    /**
     * @param  class-string<Page|Collection|Product>  $modelClass
     */
    protected function resourceHandleToUrl(string $modelClass, int $id, string $pattern): ?string
    {
        $handle = $modelClass::query()
            ->whereKey($id)
            ->value('handle');

        if (! is_string($handle) || $handle === '') {
            return null;
        }

        return sprintf($pattern, $handle);
    }
}
