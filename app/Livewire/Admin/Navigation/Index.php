<?php

namespace App\Livewire\Admin\Navigation;

use App\Enums\NavigationItemType;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    use ResolvesAdminStore;

    public ?int $editingMenuId = null;

    /** @var array<int, array<string, mixed>> */
    public array $menuItems = [];

    public ?int $editingItemIndex = null;

    public string $itemLabel = '';

    public string $itemType = 'link';

    public string $itemUrl = '';

    public ?int $itemResourceId = null;

    public bool $showItemModal = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', NavigationMenu::class);
    }

    public function selectMenu(int $menuId): void
    {
        $menu = $this->findMenu($menuId);

        $this->editingMenuId = $menu->getKey();
        $this->menuItems = $menu->navigationItems()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (NavigationItem $item): array => [
                'id' => $item->getKey(),
                'label' => $item->label,
                'type' => $item->type->value,
                'url' => (string) $item->url,
                'resource_id' => $item->resource_id,
            ])
            ->all();
    }

    public function addItem(): void
    {
        Gate::authorize('manage', NavigationMenu::class);

        $this->editingItemIndex = null;
        $this->itemLabel = '';
        $this->itemType = 'link';
        $this->itemUrl = '';
        $this->itemResourceId = null;
        $this->showItemModal = true;
    }

    public function editItem(int $index): void
    {
        Gate::authorize('manage', NavigationMenu::class);

        $item = $this->menuItems[$index] ?? null;

        abort_if($item === null, 404);

        $this->editingItemIndex = $index;
        $this->itemLabel = $item['label'];
        $this->itemType = $item['type'];
        $this->itemUrl = $item['url'];
        $this->itemResourceId = $item['resource_id'];
        $this->showItemModal = true;
    }

    public function saveItem(): void
    {
        Gate::authorize('manage', NavigationMenu::class);

        $validated = $this->validate([
            'itemLabel' => 'required|string|max:255',
            'itemType' => 'required|in:link,page,collection,product',
            'itemUrl' => 'required_if:itemType,link|nullable|string|max:2048',
            'itemResourceId' => 'required_unless:itemType,link|nullable|integer|min:1',
        ]);

        $entry = [
            'id' => $this->editingItemIndex !== null
                ? ($this->menuItems[$this->editingItemIndex]['id'] ?? null)
                : null,
            'label' => $validated['itemLabel'],
            'type' => $validated['itemType'],
            'url' => $validated['itemType'] === 'link' ? $validated['itemUrl'] : null,
            'resource_id' => $validated['itemType'] === 'link' ? null : $validated['itemResourceId'],
        ];

        if ($this->editingItemIndex !== null) {
            $this->menuItems[$this->editingItemIndex] = $entry;
        } else {
            $this->menuItems[] = $entry;
        }

        $this->showItemModal = false;
    }

    public function removeItem(int $index): void
    {
        Gate::authorize('manage', NavigationMenu::class);

        unset($this->menuItems[$index]);
        $this->menuItems = array_values($this->menuItems);
    }

    public function moveItemUp(int $index): void
    {
        if ($index > 0) {
            [$this->menuItems[$index - 1], $this->menuItems[$index]] = [$this->menuItems[$index], $this->menuItems[$index - 1]];
        }
    }

    public function moveItemDown(int $index): void
    {
        if ($index < count($this->menuItems) - 1) {
            [$this->menuItems[$index + 1], $this->menuItems[$index]] = [$this->menuItems[$index], $this->menuItems[$index + 1]];
        }
    }

    /**
     * @param  array<int>  $order  Item indexes in the new order.
     */
    public function reorderItems(array $order): void
    {
        Gate::authorize('manage', NavigationMenu::class);

        $reordered = [];

        foreach ($order as $index) {
            if (isset($this->menuItems[(int) $index])) {
                $reordered[] = $this->menuItems[(int) $index];
            }
        }

        if (count($reordered) === count($this->menuItems)) {
            $this->menuItems = $reordered;
        }
    }

    public function saveMenu(): void
    {
        Gate::authorize('manage', NavigationMenu::class);

        abort_if($this->editingMenuId === null, 404);

        $menu = $this->findMenu($this->editingMenuId);
        $keptIds = [];

        foreach (array_values($this->menuItems) as $position => $entry) {
            $data = [
                'type' => NavigationItemType::from($entry['type']),
                'label' => $entry['label'],
                'url' => $entry['url'],
                'resource_id' => $entry['resource_id'],
                'position' => $position,
            ];

            if (! empty($entry['id'])) {
                $item = $menu->navigationItems()->whereKey($entry['id'])->first();

                if ($item !== null) {
                    $item->update($data);
                    $keptIds[] = $item->getKey();

                    continue;
                }
            }

            $keptIds[] = $menu->navigationItems()->create($data)->getKey();
        }

        $menu->navigationItems()->whereNotIn('id', $keptIds)->delete();

        $this->selectMenu($menu->getKey());
        $this->toast('success', 'Navigation saved.');
    }

    public function render()
    {
        $storeId = $this->store()->getKey();

        $editingMenu = $this->editingMenuId !== null
            ? NavigationMenu::query()->where('store_id', $storeId)->whereKey($this->editingMenuId)->first()
            : null;

        $resources = match ($this->itemType) {
            'page' => Page::query()->where('store_id', $storeId)->orderBy('title')->get(['id', 'title']),
            'collection' => Collection::query()->where('store_id', $storeId)->orderBy('title')->get(['id', 'title']),
            'product' => Product::query()->where('store_id', $storeId)->orderBy('title')->get(['id', 'title']),
            default => collect(),
        };

        return view('livewire.admin.navigation.index', [
            'menus' => NavigationMenu::query()->where('store_id', $storeId)->orderBy('id')->get(),
            'editingMenu' => $editingMenu,
            'resources' => $resources,
        ])->layout('livewire.admin.layout.app', ['title' => 'Navigation']);
    }

    protected function findMenu(int $menuId): NavigationMenu
    {
        return NavigationMenu::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($menuId)
            ->firstOrFail();
    }
}
