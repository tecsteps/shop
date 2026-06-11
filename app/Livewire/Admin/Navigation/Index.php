<?php

namespace App\Livewire\Admin\Navigation;

use App\Enums\NavigationItemType;
use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Collection;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Services\NavigationService;
use Flux\Flux;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Navigation management (spec 03 section 14): menu cards, item editor with
 * drag-and-drop ordering, and a modal for link/page/collection/product
 * items. Changes are buffered in component state and persisted by saveMenu.
 */
#[Layout('layouts::admin')]
class Index extends Component
{
    use AuthorizesRequests, SendsToasts;

    public ?int $editingMenuId = null;

    /**
     * Items of the selected menu in display order.
     *
     * @var list<array{id: int|null, label: string, type: string, url: string|null, resourceId: int|null}>
     */
    public array $menuItems = [];

    public ?int $editingItemIndex = null;

    public string $itemLabel = '';

    public string $itemType = 'link';

    public string $itemUrl = '';

    /** Selected resource id for page/collection/product items (select value). */
    public string $itemResourceId = '';

    public function mount(): void
    {
        $this->authorize('viewAny', NavigationMenu::class);
    }

    public function selectMenu(int $menuId): void
    {
        $menu = NavigationMenu::query()->with('items')->findOrFail($menuId);

        $this->authorize('view', $menu);

        $this->editingMenuId = $menu->getKey();
        $this->menuItems = $menu->items
            ->map(fn ($item): array => [
                'id' => $item->getKey(),
                'label' => $item->label,
                'type' => $item->type->value,
                'url' => $item->url,
                'resourceId' => $item->resource_id,
            ])
            ->values()
            ->all();
    }

    public function addItem(): void
    {
        $this->authorize('update', $this->editingMenu());
        $this->resetErrorBag();

        $this->editingItemIndex = null;
        $this->itemLabel = '';
        $this->itemType = 'link';
        $this->itemUrl = '';
        $this->itemResourceId = '';

        Flux::modal('item-form')->show();
    }

    public function editItem(int $index): void
    {
        $this->authorize('update', $this->editingMenu());
        $this->resetErrorBag();

        $item = $this->menuItems[$index] ?? null;

        if ($item === null) {
            return;
        }

        $this->editingItemIndex = $index;
        $this->itemLabel = $item['label'];
        $this->itemType = $item['type'];
        $this->itemUrl = (string) ($item['url'] ?? '');
        $this->itemResourceId = $item['resourceId'] !== null ? (string) $item['resourceId'] : '';

        Flux::modal('item-form')->show();
    }

    public function saveItem(): void
    {
        $this->authorize('update', $this->editingMenu());

        $this->validate([
            'itemLabel' => ['required', 'string', 'max:255'],
            'itemType' => ['required', 'in:link,page,collection,product'],
            'itemUrl' => [$this->itemType === 'link' ? 'required' : 'nullable', 'string', 'max:2048'],
            'itemResourceId' => [$this->itemType !== 'link' ? 'required' : 'nullable', 'integer'],
        ]);

        $isLink = $this->itemType === NavigationItemType::Link->value;

        $item = [
            'id' => $this->editingItemIndex !== null ? ($this->menuItems[$this->editingItemIndex]['id'] ?? null) : null,
            'label' => $this->itemLabel,
            'type' => $this->itemType,
            'url' => $isLink ? $this->itemUrl : null,
            'resourceId' => $isLink ? null : (int) $this->itemResourceId,
        ];

        if ($this->editingItemIndex !== null) {
            $this->menuItems[$this->editingItemIndex] = $item;
        } else {
            $this->menuItems[] = $item;
        }

        Flux::modal('item-form')->close();

        $this->editingItemIndex = null;
    }

    public function removeItem(int $index): void
    {
        $this->authorize('update', $this->editingMenu());

        unset($this->menuItems[$index]);
        $this->menuItems = array_values($this->menuItems);
    }

    /**
     * Drag-to-reorder handler (wire:sort): the sort key is the item index.
     */
    public function reorderItems(int $index, int $position): void
    {
        $this->authorize('update', $this->editingMenu());

        $item = $this->menuItems[$index] ?? null;

        if ($item === null) {
            return;
        }

        array_splice($this->menuItems, $index, 1);
        array_splice($this->menuItems, $position, 0, [$item]);
        $this->menuItems = array_values($this->menuItems);
    }

    /**
     * Persist the buffered items and invalidate the cached navigation tree.
     */
    public function saveMenu(): void
    {
        $menu = $this->editingMenu();

        $this->authorize('update', $menu);

        DB::transaction(function () use ($menu): void {
            $keptIds = array_values(array_filter(array_column($this->menuItems, 'id')));

            $menu->items()->when(
                $keptIds !== [],
                fn ($query) => $query->whereNotIn('id', $keptIds),
            )->delete();

            foreach ($this->menuItems as $position => $item) {
                $attributes = [
                    'label' => $item['label'],
                    'type' => $item['type'],
                    'url' => $item['url'],
                    'resource_id' => $item['resourceId'],
                    'position' => $position,
                ];

                if ($item['id'] !== null) {
                    $menu->items()->whereKey($item['id'])->update($attributes);
                } else {
                    $menu->items()->create($attributes);
                }
            }
        });

        app(NavigationService::class)->forget($menu->store_id, $menu->handle);

        $this->selectMenu($menu->getKey());
        $this->toast(__('Navigation saved'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, NavigationMenu>
     */
    #[Computed]
    public function menus(): \Illuminate\Database\Eloquent\Collection
    {
        return NavigationMenu::query()->withCount('items')->orderBy('title')->get();
    }

    /**
     * Pages available for the page item type picker.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, title: string}>
     */
    #[Computed]
    public function availablePages(): \Illuminate\Support\Collection
    {
        return Page::query()->orderBy('title')->get(['id', 'title'])
            ->map(fn (Page $page): array => ['id' => $page->getKey(), 'title' => $page->title]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, title: string}>
     */
    #[Computed]
    public function availableCollections(): \Illuminate\Support\Collection
    {
        return Collection::query()->orderBy('title')->get(['id', 'title'])
            ->map(fn (Collection $collection): array => ['id' => $collection->getKey(), 'title' => $collection->title]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, title: string}>
     */
    #[Computed]
    public function availableProducts(): \Illuminate\Support\Collection
    {
        return Product::query()->orderBy('title')->get(['id', 'title'])
            ->map(fn (Product $product): array => ['id' => $product->getKey(), 'title' => $product->title]);
    }

    /**
     * Short human readable target description for an item row.
     *
     * @param  array{id: int|null, label: string, type: string, url: string|null, resourceId: int|null}  $item
     */
    public function describeItem(array $item): string
    {
        if ($item['type'] === NavigationItemType::Link->value) {
            return __('link: :url', ['url' => $item['url'] ?? '/']);
        }

        $title = match ($item['type']) {
            NavigationItemType::Page->value => $this->availablePages->firstWhere('id', $item['resourceId'])['title'] ?? null,
            NavigationItemType::Collection->value => $this->availableCollections->firstWhere('id', $item['resourceId'])['title'] ?? null,
            NavigationItemType::Product->value => $this->availableProducts->firstWhere('id', $item['resourceId'])['title'] ?? null,
            default => null,
        };

        return $item['type'].': '.($title ?? __('(missing)'));
    }

    public function render(): View
    {
        return view('livewire.admin.navigation.index')->title(__('Navigation'));
    }

    protected function editingMenu(): NavigationMenu
    {
        return NavigationMenu::query()->findOrFail($this->editingMenuId);
    }
}
