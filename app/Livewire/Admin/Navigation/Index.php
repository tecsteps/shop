<?php

namespace App\Livewire\Admin\Navigation;

use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    #[Locked]
    public ?int $storeId = null;

    /** @var SupportCollection<int, NavigationMenu> */
    public SupportCollection $menus;

    public ?int $editingMenuId = null;

    /** @var array<int, array{id: string, label: string, type: string, url: ?string, resource_id: ?string, children: array<int, array<string, mixed>>}> */
    public array $menuItems = [];

    public ?array $editingItem = null;

    public ?int $editingItemIndex = null;

    public ?int $editingItemParentIndex = null;

    public ?string $editingItemId = null;

    public string $itemLabel = '';

    public string $itemType = 'link';

    public string $itemUrl = '';

    public ?string $itemResourceId = null;

    public ?string $itemParentId = null;

    public bool $showItemModal = false;

    public function boot(): void
    {
        $this->restoreCurrentStore();
        Gate::authorize('manage-navigation');
    }

    public function mount(): void
    {
        $store = $this->currentStore();
        $this->storeId = $store->id;

        foreach ([['main-menu', 'Main Menu'], ['footer-menu', 'Footer Menu']] as [$handle, $title]) {
            NavigationMenu::query()->firstOrCreate(
                ['store_id' => $store->id, 'handle' => $handle],
                ['title' => $title],
            );
        }

        $this->refreshMenus();
        $mainMenu = $this->menus->firstWhere('handle', 'main-menu') ?? $this->menus->first();

        if ($mainMenu) {
            $this->selectMenu($mainMenu->id);
        }
    }

    public function selectMenu(int $menuId): void
    {
        Gate::authorize('manage-navigation');

        $menu = $this->menusQuery()->findOrFail($menuId);
        $this->editingMenuId = $menu->id;
        $this->loadMenuItems($menu);
    }

    public function addItem(?string $parentId = null): void
    {
        Gate::authorize('manage-navigation');
        $this->currentMenu();

        if ($parentId !== null && ! $this->rootItemExists($parentId)) {
            abort(404);
        }

        $this->resetItemForm();
        $this->itemParentId = $parentId;
        $this->showItemModal = true;
    }

    public function editItem(string $itemId): void
    {
        Gate::authorize('manage-navigation');
        $this->currentMenu();
        $location = $this->findItem($itemId);

        if (! $location) {
            abort(404);
        }

        $item = $location['item'];
        $this->editingItem = $item;
        $this->editingItemId = $itemId;
        $this->editingItemIndex = $location['index'];
        $this->editingItemParentIndex = $location['parentIndex'];
        $this->itemLabel = $item['label'];
        $this->itemType = $item['type'];
        $this->itemUrl = (string) ($item['url'] ?? '');
        $this->itemResourceId = isset($item['resource_id']) ? (string) $item['resource_id'] : null;
        $this->itemParentId = $location['parentId'];
        $this->showItemModal = true;
    }

    public function saveItem(): void
    {
        Gate::authorize('manage-navigation');
        $this->currentMenu();

        $this->itemParentId = filled($this->itemParentId) ? $this->itemParentId : null;
        $store = $this->currentStore();
        $resourceTable = $this->resourceTable($this->itemType);
        $rules = [
            'itemLabel' => ['required', 'string', 'max:255'],
            'itemType' => ['required', Rule::in(['link', 'page', 'collection', 'product'])],
            'itemUrl' => ['nullable', 'string', 'max:2048'],
            'itemResourceId' => ['nullable', 'integer'],
            'itemParentId' => ['nullable', 'string', 'max:80'],
        ];

        if ($this->itemType === 'link') {
            $rules['itemUrl'] = ['required', 'string', 'max:2048'];
        } elseif ($resourceTable !== null) {
            $rules['itemResourceId'] = [
                'required',
                'integer',
                Rule::exists($resourceTable, 'id')->where('store_id', $store->id),
            ];
        }

        $validated = $this->validate($rules);

        if ($this->itemType === 'link' && ! $this->isAllowedNavigationUrl($this->itemUrl)) {
            $this->addError('itemUrl', 'Enter a safe relative URL or an HTTP or HTTPS URL.');

            return;
        }

        if ($this->itemParentId !== null && ! $this->rootItemExists($this->itemParentId)) {
            $this->addError('itemParentId', 'Choose a top-level item in this menu.');

            return;
        }

        if ($this->editingItemId !== null && $this->itemParentId === $this->editingItemId) {
            $this->addError('itemParentId', 'An item cannot be its own parent.');

            return;
        }

        $oldItem = $this->editingItemId === null ? null : $this->findItem($this->editingItemId)['item'] ?? null;
        $children = $oldItem['children'] ?? [];

        if ($this->itemParentId !== null && $children !== []) {
            $this->addError('itemParentId', 'An item with submenu links must remain at the top level.');

            return;
        }

        $item = [
            'id' => $this->editingItemId ?? 'new-'.Str::uuid(),
            'label' => $validated['itemLabel'],
            'type' => $validated['itemType'],
            'url' => $validated['itemType'] === 'link' ? $validated['itemUrl'] : null,
            'resource_id' => $validated['itemType'] === 'link' ? null : (string) $validated['itemResourceId'],
            'children' => $children,
        ];

        if ($this->editingItemId !== null) {
            $this->removeItemFromTree($this->editingItemId);
        }

        $this->insertItem($item, $this->itemParentId);
        $this->resetItemForm();
        $this->showItemModal = false;
    }

    public function removeItem(string $itemId): void
    {
        Gate::authorize('manage-navigation');
        $this->currentMenu();

        if (! $this->removeItemFromTree($itemId)) {
            abort(404);
        }
    }

    public function reorderItems(array|string $itemId, ?int $position = null, string $parentId = 'root'): void
    {
        Gate::authorize('manage-navigation');
        $this->currentMenu();

        if (is_array($itemId)) {
            $this->reorderMenuGroups($itemId);

            return;
        }

        if ($position === null) {
            $this->addError('menuItems', 'The new item position is required.');

            return;
        }

        $location = $this->findItem($itemId);

        if (! $location) {
            abort(404);
        }

        $newParentId = $parentId === 'root' ? null : $parentId;

        if ($newParentId !== null && ! $this->rootItemExists($newParentId)) {
            abort(404);
        }

        if ($newParentId === $itemId) {
            $this->addError('menuItems', 'An item cannot be its own parent.');

            return;
        }

        if ($newParentId !== null && ($location['item']['children'] ?? []) !== []) {
            $this->addError('menuItems', 'An item with submenu links must remain at the top level.');

            return;
        }

        $item = $this->removeItemFromTree($itemId);

        if (! is_array($item)) {
            abort(404);
        }

        $this->insertItem($item, $newParentId, $position);
    }

    public function saveMenu(): void
    {
        Gate::authorize('manage-navigation');
        $menu = $this->currentMenu();
        $menuItems = $this->validatedMenuItems();

        DB::transaction(function () use ($menu, $menuItems): void {
            $menu->items()->delete();

            foreach ($menuItems as $position => $item) {
                $parent = $menu->items()->create($this->itemAttributes($item, $position));

                foreach ($item['children'] ?? [] as $childPosition => $child) {
                    NavigationItem::query()->create([
                        'menu_id' => $menu->id,
                        'parent_id' => $parent->id,
                        ...$this->itemAttributes($child, $childPosition),
                    ]);
                }
            }
        });

        $this->loadMenuItems($this->currentMenu());
        session()->flash('status', 'Navigation menu saved.');
    }

    public function render(): mixed
    {
        $store = $this->currentStore();
        $resources = [
            'pages' => Page::query()->where('store_id', $store->id)->orderBy('title')->get(['id', 'title']),
            'collections' => Collection::query()->where('store_id', $store->id)->orderBy('title')->get(['id', 'title']),
            'products' => Product::query()->where('store_id', $store->id)->orderBy('title')->get(['id', 'title']),
        ];

        return view('livewire.admin.navigation.index', [
            'menus' => $this->menusQuery()->withCount('items')->get(),
            'selectedMenu' => $this->editingMenu,
            'resources' => $resources,
        ])->layout('layouts.admin');
    }

    #[Computed]
    public function editingMenu(): ?NavigationMenu
    {
        return $this->editingMenuId === null ? null : $this->menusQuery()->find($this->editingMenuId);
    }

    private function refreshMenus(): void
    {
        $this->menus = $this->menusQuery()->orderBy('id')->get();
    }

    private function menusQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return NavigationMenu::query()->where('store_id', $this->currentStore()->id);
    }

    private function currentMenu(): NavigationMenu
    {
        if ($this->editingMenuId === null) {
            abort(404);
        }

        return $this->menusQuery()->findOrFail($this->editingMenuId);
    }

    private function currentStore(): Store
    {
        $store = app()->bound('current_store') ? app('current_store') : null;

        abort_unless($store instanceof Store, 403);

        return $store;
    }

    private function restoreCurrentStore(): void
    {
        if (app()->bound('current_store') || $this->storeId === null) {
            return;
        }

        app()->instance('current_store', Store::query()->findOrFail($this->storeId));
    }

    private function loadMenuItems(NavigationMenu $menu): void
    {
        $this->menuItems = $menu->items()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('position')
            ->get()
            ->map(fn (NavigationItem $item): array => $this->toMenuItemArray($item))
            ->all();
    }

    /** @return array{id: string, label: string, type: string, url: ?string, resource_id: ?string, children: array<int, array<string, mixed>>} */
    private function toMenuItemArray(NavigationItem $item): array
    {
        return [
            'id' => (string) $item->id,
            'label' => $item->label,
            'type' => $item->type,
            'url' => $item->url,
            'resource_id' => $item->resource_id === null ? null : (string) $item->resource_id,
            'children' => $item->children->map(fn (NavigationItem $child): array => [
                'id' => (string) $child->id,
                'label' => $child->label,
                'type' => $child->type,
                'url' => $child->url,
                'resource_id' => $child->resource_id === null ? null : (string) $child->resource_id,
                'children' => [],
            ])->all(),
        ];
    }

    private function resetItemForm(): void
    {
        $this->resetValidation();
        $this->editingItem = null;
        $this->editingItemId = null;
        $this->editingItemIndex = null;
        $this->editingItemParentIndex = null;
        $this->itemLabel = '';
        $this->itemType = 'link';
        $this->itemUrl = '';
        $this->itemResourceId = null;
        $this->itemParentId = null;
    }

    /** @return array{parentId: ?string, parentIndex: ?int, index: int, item: array<string, mixed>}|null */
    private function findItem(string $itemId): ?array
    {
        foreach ($this->menuItems as $index => $item) {
            if ((string) $item['id'] === $itemId) {
                return ['parentId' => null, 'parentIndex' => null, 'index' => $index, 'item' => $item];
            }

            foreach ($item['children'] ?? [] as $childIndex => $child) {
                if ((string) $child['id'] === $itemId) {
                    return ['parentId' => (string) $item['id'], 'parentIndex' => $index, 'index' => $childIndex, 'item' => $child];
                }
            }
        }

        return null;
    }

    private function rootItemExists(string $itemId): bool
    {
        foreach ($this->menuItems as $item) {
            if ((string) $item['id'] === $itemId) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed>|null */
    private function removeItemFromTree(string $itemId): ?array
    {
        foreach ($this->menuItems as $index => $item) {
            if ((string) $item['id'] === $itemId) {
                array_splice($this->menuItems, $index, 1);

                return $item;
            }

            foreach ($item['children'] ?? [] as $childIndex => $child) {
                if ((string) $child['id'] === $itemId) {
                    array_splice($this->menuItems[$index]['children'], $childIndex, 1);

                    return $child;
                }
            }
        }

        return null;
    }

    /** @param array<string, mixed> $item */
    private function insertItem(array $item, ?string $parentId, ?int $position = null): void
    {
        $position = max(0, $position ?? PHP_INT_MAX);

        if ($parentId === null) {
            array_splice($this->menuItems, min($position, count($this->menuItems)), 0, [$item]);
            $this->menuItems = array_values($this->menuItems);

            return;
        }

        foreach ($this->menuItems as $index => $parent) {
            if ((string) $parent['id'] !== $parentId) {
                continue;
            }

            $children = $parent['children'] ?? [];
            array_splice($children, min($position, count($children)), 0, [$item]);
            $this->menuItems[$index]['children'] = array_values($children);

            return;
        }

        abort(404);
    }

    /** @param array<int|string, mixed> $order */
    private function reorderMenuGroups(array $order): void
    {
        if (array_is_list($order)) {
            $orderedItems = $this->orderedItems($this->menuItems, $order);

            if ($orderedItems === null) {
                $this->addError('menuItems', 'The menu order is invalid.');

                return;
            }

            $this->menuItems = $orderedItems;

            return;
        }

        foreach ($order as $parentId => $itemIds) {
            if (! is_array($itemIds)) {
                $this->addError('menuItems', 'The menu order is invalid.');

                return;
            }

            if ((string) $parentId === 'root') {
                $orderedItems = $this->orderedItems($this->menuItems, $itemIds);

                if ($orderedItems === null) {
                    $this->addError('menuItems', 'The top-level menu order is invalid.');

                    return;
                }

                $this->menuItems = $orderedItems;

                continue;
            }

            foreach ($this->menuItems as $index => $item) {
                if ((string) $item['id'] !== (string) $parentId) {
                    continue;
                }

                $orderedItems = $this->orderedItems($item['children'] ?? [], $itemIds);

                if ($orderedItems === null) {
                    $this->addError('menuItems', 'A submenu order is invalid.');

                    return;
                }

                $this->menuItems[$index]['children'] = $orderedItems;

                continue 2;
            }

            abort(404);
        }
    }

    /** @param array<int, array<string, mixed>> $items
     * @param  array<int, mixed>  $order
     * @return array<int, array<string, mixed>>|null
     */
    private function orderedItems(array $items, array $order): ?array
    {
        if (count($items) !== count($order)) {
            return null;
        }

        $itemsById = [];

        foreach ($items as $item) {
            $itemsById[(string) $item['id']] = $item;
        }

        $orderedItems = [];

        foreach ($order as $itemId) {
            if (! is_string($itemId) && ! is_int($itemId)) {
                return null;
            }

            $itemKey = (string) $itemId;

            if (! array_key_exists($itemKey, $itemsById)) {
                return null;
            }

            $orderedItems[] = $itemsById[$itemKey];
            unset($itemsById[$itemKey]);
        }

        return $itemsById === [] ? $orderedItems : null;
    }

    /** @return array<string, mixed> */
    private function itemAttributes(array $item, int $position): array
    {
        $resourceId = ($item['type'] ?? null) === 'link' ? null : (int) $item['resource_id'];

        return [
            'type' => $item['type'],
            'label' => $item['label'],
            'url' => ($item['type'] ?? null) === 'link' ? $item['url'] : null,
            'resource_id' => $resourceId,
            'position' => $position,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function validatedMenuItems(): array
    {
        $rules = [
            'menuItems' => ['present', 'array'],
            'menuItems.*' => ['required', 'array:id,label,type,url,resource_id,children'],
            'menuItems.*.id' => ['nullable', 'string', 'max:80'],
            'menuItems.*.label' => ['required', 'string', 'max:255'],
            'menuItems.*.type' => ['required', Rule::in(['link', 'page', 'collection', 'product'])],
            'menuItems.*.url' => ['nullable', 'string', 'max:2048'],
            'menuItems.*.resource_id' => ['nullable', 'integer'],
            'menuItems.*.children' => ['present', 'array'],
            'menuItems.*.children.*' => ['required', 'array:id,label,type,url,resource_id,children'],
            'menuItems.*.children.*.id' => ['nullable', 'string', 'max:80'],
            'menuItems.*.children.*.label' => ['required', 'string', 'max:255'],
            'menuItems.*.children.*.type' => ['required', Rule::in(['link', 'page', 'collection', 'product'])],
            'menuItems.*.children.*.url' => ['nullable', 'string', 'max:2048'],
            'menuItems.*.children.*.resource_id' => ['nullable', 'integer'],
            'menuItems.*.children.*.children' => ['present', 'array', 'size:0'],
        ];

        $validator = Validator::make(['menuItems' => $this->menuItems], $rules);
        $store = $this->currentStore();
        $validator->after(function ($validator) use ($store): void {
            foreach ($this->menuItems as $index => $item) {
                $this->validateTarget($validator, $item, "menuItems.{$index}", $store);

                foreach ($item['children'] ?? [] as $childIndex => $child) {
                    $this->validateTarget($validator, $child, "menuItems.{$index}.children.{$childIndex}", $store);
                }
            }
        });

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return $validator->validated()['menuItems'];
    }

    /** @param array<string, mixed> $item */
    private function validateTarget(\Illuminate\Validation\Validator $validator, array $item, string $key, Store $store): void
    {
        if (! in_array($item['type'] ?? null, ['link', 'page', 'collection', 'product'], true)) {
            return;
        }

        if ($item['type'] === 'link') {
            if (! $this->isAllowedNavigationUrl((string) ($item['url'] ?? ''))) {
                $validator->errors()->add("{$key}.url", 'Enter a safe relative URL or an HTTP or HTTPS URL.');
            }

            return;
        }

        $class = $this->resourceClass($item['type']);
        $resourceId = $item['resource_id'] ?? null;

        if (! $class || ! is_numeric($resourceId) || ! $class::query()->where('store_id', $store->id)->whereKey((int) $resourceId)->exists()) {
            $validator->errors()->add("{$key}.resource_id", 'Choose an available resource from this store.');
        }
    }

    private function resourceTable(string $type): ?string
    {
        return match ($type) {
            'page' => 'pages',
            'collection' => 'collections',
            'product' => 'products',
            default => null,
        };
    }

    /** @return class-string<Model>|null */
    private function resourceClass(string $type): ?string
    {
        return match ($type) {
            'page' => Page::class,
            'collection' => Collection::class,
            'product' => Product::class,
            default => null,
        };
    }

    private function isAllowedNavigationUrl(string $url): bool
    {
        if ($url === '' || preg_match('/[\x00-\x20\\\\]/', $url) === 1) {
            return false;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array(strtolower((string) $scheme), ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
