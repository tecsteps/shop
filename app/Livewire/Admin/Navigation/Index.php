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
     * @var array<int, array{id: int|null, label: string, type: string, url: string|null, resource_id: int|null}>
     */
    public array $menuItems = [];

    public ?int $editingItemIndex = null;

    public string $itemLabel = '';

    public string $itemType = 'link';

    public string $itemUrl = '';

    public ?int $itemResourceId = null;

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
    }

    public function saveItem(): void
    {
        $this->authorize('update', $this->scopedStore());

        $validated = $this->validate([
            'itemLabel' => ['required', 'string', 'max:255'],
            'itemType' => ['required', Rule::in(array_column(NavigationItemType::cases(), 'value'))],
            'itemUrl' => [Rule::requiredIf($this->itemType === NavigationItemType::Link->value), 'nullable', 'string', 'max:255'],
            'itemResourceId' => [Rule::requiredIf($this->itemType !== NavigationItemType::Link->value), 'nullable', 'integer'],
        ], [], [
            'itemLabel' => 'label',
            'itemType' => 'type',
            'itemUrl' => 'URL',
            'itemResourceId' => 'resource',
        ]);

        if ($this->itemType !== NavigationItemType::Link->value && ! $this->resourceExists($this->itemType, (int) $this->itemResourceId)) {
            $this->addError('itemResourceId', __('Select a valid resource for this store.'));

            return;
        }

        $item = [
            'id' => $this->editingItemIndex !== null ? $this->menuItems[$this->editingItemIndex]['id'] : null,
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
        unset($this->menuItems[$index]);

        $this->menuItems = array_values($this->menuItems);
    }

    public function moveItemUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->menuItems[$index])) {
            return;
        }

        [$this->menuItems[$index - 1], $this->menuItems[$index]] = [$this->menuItems[$index], $this->menuItems[$index - 1]];
    }

    public function moveItemDown(int $index): void
    {
        if (! isset($this->menuItems[$index], $this->menuItems[$index + 1])) {
            return;
        }

        [$this->menuItems[$index], $this->menuItems[$index + 1]] = [$this->menuItems[$index + 1], $this->menuItems[$index]];
    }

    public function saveMenu(NavigationService $navigation): void
    {
        $this->authorize('update', $this->scopedStore());

        $menu = $this->selectedMenu();

        DB::transaction(function () use ($menu): void {
            NavigationItem::withoutGlobalScopes()
                ->where('menu_id', $menu->getKey())
                ->delete();

            foreach ($this->menuItems as $position => $item) {
                NavigationItem::withoutGlobalScopes()->create([
                    'menu_id' => $menu->getKey(),
                    'type' => NavigationItemType::from($item['type']),
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'resource_id' => $item['resource_id'],
                    'position' => $position,
                ]);
            }
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
        $this->resetErrorBag(['itemLabel', 'itemType', 'itemUrl', 'itemResourceId']);
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

    public function render(): mixed
    {
        return view('livewire.admin.navigation.index', [
            'menus' => $this->menus(),
            'selectedMenu' => $this->selectedMenu(),
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
            ->orderBy('position')
            ->get()
            ->map(fn (NavigationItem $item): array => [
                'id' => $item->getKey(),
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
}
