<?php

namespace App\Livewire\Admin\Navigation;

use App\Enums\NavigationItemType;
use App\Models\Collection as ProductCollection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use App\Services\NavigationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $storeId;

    public ?int $selectedMenuId = null;

    /**
     * @var array<int, array{id: int|null, key: string, parent_key: string|null, label: string, type: string, url: string|null, resource_id: int|null}>
     */
    public array $menuItems = [];

    public ?int $editingItemIndex = null;

    public string $itemLabel = '';

    public string $itemType = 'link';

    public string $itemUrl = '';

    public ?int $itemResourceId = null;

    public string $itemParentKey = '';

    public function mount(): void
    {
        $store = $this->store();

        $this->authorize('update', $store);

        $this->storeId = $store->getKey();
        $this->selectedMenuId = $this->menus()->first()?->getKey();

        if ($this->selectedMenuId !== null) {
            $this->loadMenuItems();
        }
    }

    public function selectMenu(int $menuId): void
    {
        $this->authorize('update', $this->scopedStore());

        $this->selectedMenuId = $this->menu($menuId)->getKey();
        $this->loadMenuItems();
        $this->cancelItem();
    }

    public function addItem(): void
    {
        $this->editingItemIndex = null;
        $this->itemLabel = '';
        $this->itemType = 'link';
        $this->itemUrl = '';
        $this->itemResourceId = null;
        $this->itemParentKey = '';
    }

    public function editItem(int $index): void
    {
        abort_unless(isset($this->menuItems[$index]), 404);

        $item = $this->menuItems[$index];

        $this->editingItemIndex = $index;
        $this->itemLabel = $item['label'];
        $this->itemType = $item['type'];
        $this->itemUrl = (string) ($item['url'] ?? '');
        $this->itemResourceId = $item['resource_id'];
        $this->itemParentKey = (string) ($item['parent_key'] ?? '');
    }

    public function saveItem(): void
    {
        $this->authorize('update', $this->scopedStore());

        $validated = $this->validate([
            'itemLabel' => ['required', 'string', 'max:255'],
            'itemType' => ['required', Rule::in(array_column(NavigationItemType::cases(), 'value'))],
            'itemUrl' => [Rule::requiredIf($this->itemType === NavigationItemType::Link->value), 'nullable', 'string', 'max:255'],
            'itemResourceId' => [Rule::requiredIf($this->itemType !== NavigationItemType::Link->value), 'nullable', 'integer'],
            'itemParentKey' => ['nullable', 'string'],
        ], [], [
            'itemLabel' => 'label',
            'itemType' => 'type',
            'itemUrl' => 'URL',
            'itemResourceId' => 'resource',
            'itemParentKey' => 'parent item',
        ]);

        $parentKey = $this->normalizedParentKey($validated['itemParentKey']);

        $currentKey = $this->editingItemIndex !== null ? $this->menuItems[$this->editingItemIndex]['key'] : null;

        if (! $this->validParentKey($parentKey, $currentKey)) {
            $this->addError('itemParentKey', __('Select a valid top-level parent item.'));

            return;
        }

        if ($this->editingItemIndex !== null
            && $parentKey !== null
            && $this->itemHasChildren($this->menuItems[$this->editingItemIndex]['key'])) {
            $this->addError('itemParentKey', __('Items with children must stay top-level.'));

            return;
        }

        if ($this->itemType !== NavigationItemType::Link->value && ! $this->resourceExists($this->itemType, (int) $this->itemResourceId)) {
            $this->addError('itemResourceId', __('Select a valid resource for this store.'));

            return;
        }

        $item = [
            'id' => $this->editingItemIndex !== null ? $this->menuItems[$this->editingItemIndex]['id'] : null,
            'key' => $this->editingItemIndex !== null ? $this->menuItems[$this->editingItemIndex]['key'] : 'new-'.Str::uuid()->toString(),
            'parent_key' => $parentKey,
            'label' => $validated['itemLabel'],
            'type' => $validated['itemType'],
            'url' => $this->itemType === NavigationItemType::Link->value ? $validated['itemUrl'] : null,
            'resource_id' => $this->itemType === NavigationItemType::Link->value ? null : (int) $this->itemResourceId,
        ];

        if ($this->editingItemIndex === null) {
            $this->menuItems[] = $item;
        } else {
            $this->menuItems[$this->editingItemIndex] = $item;
        }

        $this->cancelItem();
    }

    public function removeItem(int $index): void
    {
        $removedKey = $this->menuItems[$index]['key'] ?? null;

        unset($this->menuItems[$index]);

        $this->menuItems = array_values(array_filter(
            $this->menuItems,
            fn (array $item): bool => $removedKey === null || ($item['parent_key'] ?? null) !== $removedKey,
        ));
    }

    public function moveItemUp(int $index): void
    {
        if (! isset($this->menuItems[$index])) {
            return;
        }

        $this->moveSibling($index, -1);
    }

    public function moveItemDown(int $index): void
    {
        if (! isset($this->menuItems[$index])) {
            return;
        }

        $this->moveSibling($index, 1);
    }

    public function reorderItem(string $key, int $position, string $parentKey = 'root'): void
    {
        $targetParentKey = $this->normalizedParentKey($parentKey === 'root' ? '' : $parentKey);

        if (! $this->validParentKey($targetParentKey, $key) || ($targetParentKey !== null && $this->itemHasChildren($key))) {
            return;
        }

        $index = $this->itemIndexForKey($key);

        if ($index === null) {
            return;
        }

        $item = $this->menuItems[$index];
        array_splice($this->menuItems, $index, 1);

        $item['parent_key'] = $targetParentKey;
        $siblingIndexes = $this->siblingIndices($targetParentKey);
        $insertAt = $siblingIndexes[$position] ?? (count($this->menuItems));

        if ($position >= count($siblingIndexes) && $siblingIndexes !== []) {
            $insertAt = ((int) end($siblingIndexes)) + 1;
        }

        array_splice($this->menuItems, $insertAt, 0, [$item]);
        $this->menuItems = array_values($this->menuItems);
    }

    public function saveMenu(NavigationService $navigation): void
    {
        $this->authorize('update', $this->scopedStore());

        $menu = $this->selectedMenu();

        DB::transaction(function () use ($menu): void {
            NavigationItem::withoutGlobalScopes()
                ->where('menu_id', $menu->getKey())
                ->delete();

            $this->persistMenuItems($menu);
        });

        $navigation->forget($menu);
        $this->loadMenuItems();

        session()->flash('status', 'Navigation saved');
        $this->dispatch('toast', type: 'success', message: __('Navigation saved'));
    }

    public function cancelItem(): void
    {
        $this->editingItemIndex = null;
        $this->itemLabel = '';
        $this->itemType = 'link';
        $this->itemUrl = '';
        $this->itemResourceId = null;
        $this->itemParentKey = '';
        $this->resetErrorBag(['itemLabel', 'itemType', 'itemUrl', 'itemResourceId', 'itemParentKey']);
    }

    /**
     * @return Collection<int, NavigationMenu>
     */
    public function menus(): Collection
    {
        return NavigationMenu::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->orderByRaw("case when handle = 'main-menu' then 0 when handle = 'footer-menu' then 1 else 2 end")
            ->orderBy('title')
            ->get();
    }

    /**
     * @return Collection<int, Page|ProductCollection|Product>
     */
    public function resourcesForType(): Collection
    {
        return match ($this->itemType) {
            NavigationItemType::Page->value => Page::withoutGlobalScopes()
                ->where('store_id', $this->storeId)
                ->orderBy('title')
                ->get(['id', 'title']),
            NavigationItemType::Collection->value => ProductCollection::withoutGlobalScopes()
                ->where('store_id', $this->storeId)
                ->orderBy('title')
                ->get(['id', 'title']),
            NavigationItemType::Product->value => Product::withoutGlobalScopes()
                ->where('store_id', $this->storeId)
                ->orderBy('title')
                ->limit(100)
                ->get(['id', 'title']),
            default => collect(),
        };
    }

    public function targetLabel(array $item): string
    {
        return match ($item['type']) {
            NavigationItemType::Page->value => 'page: '.$this->resourceTitle(Page::class, $item['resource_id']),
            NavigationItemType::Collection->value => 'collection: '.$this->resourceTitle(ProductCollection::class, $item['resource_id']),
            NavigationItemType::Product->value => 'product: '.$this->resourceTitle(Product::class, $item['resource_id']),
            default => 'link: '.($item['url'] ?: '#'),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function nestedMenuItems(): array
    {
        $itemsByParent = collect($this->menuItems)
            ->groupBy(fn (array $item): string => $item['parent_key'] ?? 'root', preserveKeys: true);

        $build = function (string $parentKey) use (&$build, $itemsByParent): array {
            return $itemsByParent->get($parentKey, collect())
                ->map(fn (array $item, int $index): array => array_merge($item, [
                    'index' => $index,
                    'children' => $build($item['key']),
                ]))
                ->values()
                ->all();
        };

        return $build('root');
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function parentOptions(): array
    {
        $editingKey = $this->editingItemIndex !== null ? $this->menuItems[$this->editingItemIndex]['key'] : null;

        return collect($this->menuItems)
            ->filter(fn (array $item): bool => ($item['parent_key'] ?? null) === null && $item['key'] !== $editingKey)
            ->map(fn (array $item): array => [
                'key' => $item['key'],
                'label' => $item['label'],
            ])
            ->values()
            ->all();
    }

    public function render(): mixed
    {
        return view('livewire.admin.navigation.index', [
            'menus' => $this->menus(),
            'selectedMenu' => $this->selectedMenu(),
            'navigationTree' => $this->nestedMenuItems(),
            'parentOptions' => $this->parentOptions(),
            'resources' => $this->resourcesForType(),
        ])->layout('layouts.app', [
            'title' => __('Navigation'),
        ]);
    }

    private function store(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function scopedStore(): Store
    {
        return Store::query()->whereKey($this->storeId)->firstOrFail();
    }

    private function selectedMenu(): NavigationMenu
    {
        abort_unless($this->selectedMenuId !== null, 404);

        return $this->menu($this->selectedMenuId);
    }

    private function menu(int $menuId): NavigationMenu
    {
        return NavigationMenu::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->whereKey($menuId)
            ->firstOrFail();
    }

    private function loadMenuItems(): void
    {
        $this->menuItems = NavigationItem::withoutGlobalScopes()
            ->where('menu_id', $this->selectedMenu()->getKey())
            ->orderBy('parent_id')
            ->orderBy('position')
            ->get()
            ->map(fn (NavigationItem $item): array => [
                'id' => $item->getKey(),
                'key' => (string) $item->getKey(),
                'parent_key' => $item->parent_id === null ? null : (string) $item->parent_id,
                'label' => $item->label,
                'type' => $item->type->value,
                'url' => $item->url,
                'resource_id' => $item->resource_id,
            ])
            ->all();
    }

    private function resourceExists(string $type, int $resourceId): bool
    {
        return match ($type) {
            NavigationItemType::Page->value => Page::withoutGlobalScopes()->where('store_id', $this->storeId)->whereKey($resourceId)->exists(),
            NavigationItemType::Collection->value => ProductCollection::withoutGlobalScopes()->where('store_id', $this->storeId)->whereKey($resourceId)->exists(),
            NavigationItemType::Product->value => Product::withoutGlobalScopes()->where('store_id', $this->storeId)->whereKey($resourceId)->exists(),
            default => true,
        };
    }

    private function resourceTitle(string $modelClass, mixed $resourceId): string
    {
        if (! is_numeric($resourceId)) {
            return 'Missing';
        }

        return $modelClass::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->whereKey((int) $resourceId)
            ->value('title') ?? 'Missing';
    }

    private function persistMenuItems(NavigationMenu $menu): void
    {
        $itemsByParent = collect($this->menuItems)
            ->groupBy(fn (array $item): string => $item['parent_key'] ?? 'root');

        $persist = function (string $parentKey, ?int $parentId = null) use (&$persist, $itemsByParent, $menu): void {
            $itemsByParent->get($parentKey, collect())
                ->values()
                ->each(function (array $item, int $position) use (&$persist, $menu, $parentId): void {
                    $navigationItem = NavigationItem::withoutGlobalScopes()->create([
                        'menu_id' => $menu->getKey(),
                        'parent_id' => $parentId,
                        'type' => NavigationItemType::from($item['type']),
                        'label' => $item['label'],
                        'url' => $item['url'],
                        'resource_id' => $item['resource_id'],
                        'position' => $position,
                    ]);

                    $persist($item['key'], $navigationItem->getKey());
                });
        };

        $persist('root');
    }

    private function moveSibling(int $index, int $direction): void
    {
        $parentKey = $this->menuItems[$index]['parent_key'] ?? null;
        $siblingIndices = $this->siblingIndices($parentKey);
        $siblingPosition = array_search($index, $siblingIndices, true);

        if ($siblingPosition === false) {
            return;
        }

        $targetSiblingPosition = $siblingPosition + $direction;

        if (! isset($siblingIndices[$targetSiblingPosition])) {
            return;
        }

        $targetIndex = $siblingIndices[$targetSiblingPosition];
        [$this->menuItems[$index], $this->menuItems[$targetIndex]] = [$this->menuItems[$targetIndex], $this->menuItems[$index]];
        $this->menuItems = array_values($this->menuItems);
    }

    /**
     * @return list<int>
     */
    private function siblingIndices(?string $parentKey): array
    {
        return array_values(array_keys(array_filter(
            $this->menuItems,
            fn (array $item): bool => ($item['parent_key'] ?? null) === $parentKey,
        )));
    }

    private function normalizedParentKey(?string $parentKey): ?string
    {
        $parentKey = trim((string) $parentKey);

        return $parentKey === '' ? null : $parentKey;
    }

    private function validParentKey(?string $parentKey, ?string $movingKey = null): bool
    {
        if ($parentKey === null) {
            return true;
        }

        foreach ($this->menuItems as $item) {
            if ($item['key'] === $parentKey
                && ($item['parent_key'] ?? null) === null
                && $item['key'] !== $movingKey) {
                return true;
            }
        }

        return false;
    }

    private function itemHasChildren(string $key): bool
    {
        foreach ($this->menuItems as $item) {
            if (($item['parent_key'] ?? null) === $key) {
                return true;
            }
        }

        return false;
    }

    private function itemIndexForKey(string $key): ?int
    {
        foreach ($this->menuItems as $index => $item) {
            if ($item['key'] === $key) {
                return $index;
            }
        }

        return null;
    }
}
