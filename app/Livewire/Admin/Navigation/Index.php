<?php

namespace App\Livewire\Admin\Navigation;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    use DispatchesToasts;

    #[Layout('layouts.admin.app')]
    public ?int $selectedMenuId = null;

    /**
     * @var list<array{id: ?int, label: string, type: string, url: ?string, resource_id: ?int}>
     */
    public array $menuItems = [];

    public bool $showItemModal = false;

    public ?int $editingItemIndex = null;

    public string $itemLabel = '';

    public string $itemType = 'link';

    public string $itemUrl = '';

    public ?int $itemResourceId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', NavigationMenu::class);
    }

    #[Computed]
    public function menus(): SupportCollection
    {
        return app('current_store')->navigationMenus()->with('items')->orderBy('title')->get();
    }

    #[Computed]
    public function availablePages(): SupportCollection
    {
        return Page::query()->orderBy('title')->get();
    }

    #[Computed]
    public function availableCollections(): SupportCollection
    {
        return Collection::query()->orderBy('title')->get();
    }

    #[Computed]
    public function availableProducts(): SupportCollection
    {
        return Product::query()->orderBy('title')->get();
    }

    public function selectMenu(int $menuId): void
    {
        $this->selectedMenuId = $menuId;

        $this->menuItems = NavigationItem::where('menu_id', $menuId)
            ->orderBy('position')
            ->get()
            ->map(fn (NavigationItem $item) => [
                'id' => $item->id,
                'label' => $item->label,
                'type' => $item->type,
                'url' => $item->url,
                'resource_id' => $item->resource_id,
            ])
            ->all();
    }

    public function addItem(): void
    {
        $this->resetItemForm();
        $this->editingItemIndex = null;
        $this->showItemModal = true;
    }

    public function editItem(int $index): void
    {
        $item = $this->menuItems[$index];

        $this->itemLabel = $item['label'];
        $this->itemType = $item['type'];
        $this->itemUrl = (string) ($item['url'] ?? '');
        $this->itemResourceId = $item['resource_id'];
        $this->editingItemIndex = $index;
        $this->showItemModal = true;
    }

    public function saveItem(): void
    {
        $this->validate([
            'itemLabel' => ['required', 'string', 'max:255'],
            'itemType' => ['required', 'in:link,page,collection,product'],
            'itemUrl' => ['nullable', 'string', 'max:255'],
            'itemResourceId' => ['nullable', 'integer'],
        ]);

        $item = [
            'id' => $this->editingItemIndex !== null ? ($this->menuItems[$this->editingItemIndex]['id'] ?? null) : null,
            'label' => $this->itemLabel,
            'type' => $this->itemType,
            'url' => $this->itemUrl,
            'resource_id' => $this->itemType === 'link' ? null : $this->itemResourceId,
        ];

        if ($this->editingItemIndex !== null) {
            $this->menuItems[$this->editingItemIndex] = $item;
        } else {
            $this->menuItems[] = $item;
        }

        $this->showItemModal = false;
    }

    public function removeItem(int $index): void
    {
        unset($this->menuItems[$index]);
        $this->menuItems = array_values($this->menuItems);
    }

    public function moveItem(int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($this->menuItems[$target])) {
            return;
        }

        $item = $this->menuItems[$index];
        $this->menuItems[$index] = $this->menuItems[$target];
        $this->menuItems[$target] = $item;
    }

    public function reorderItems(array $order): void
    {
        $items = $this->menuItems;
        $reordered = [];

        foreach ($order as $index) {
            if (isset($items[$index])) {
                $reordered[] = $items[$index];
            }
        }

        $this->menuItems = $reordered;
    }

    public function saveMenu(): void
    {
        if ($this->selectedMenuId === null) {
            return;
        }

        $this->authorize('manage', NavigationMenu::class);

        NavigationItem::where('menu_id', $this->selectedMenuId)->delete();

        foreach ($this->menuItems as $position => $item) {
            NavigationItem::create([
                'menu_id' => $this->selectedMenuId,
                'type' => $item['type'],
                'label' => $item['label'],
                'url' => $item['type'] === 'link' ? ($item['url'] !== '' ? $item['url'] : null) : null,
                'resource_id' => $item['type'] === 'link' ? null : ($item['resource_id'] ?? null),
                'position' => $position,
            ]);
        }

        $this->toast('Navigation saved');

        $this->selectMenu($this->selectedMenuId);
    }

    private function resetItemForm(): void
    {
        $this->itemLabel = '';
        $this->itemType = 'link';
        $this->itemUrl = '';
        $this->itemResourceId = null;
    }

    public function render()
    {
        return view('livewire.admin.navigation.index');
    }
}
