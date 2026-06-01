<?php

namespace App\Livewire\Admin\Navigation;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Collection;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Services\NavigationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Navigation menu management: pick a menu, then add / edit / reorder / remove
 * its items (drag-and-drop ordering via wire:sort). Persisting a menu flushes
 * the storefront navigation cache.
 */
#[Layout('livewire.admin.layout.app')]
class Index extends Component
{
    use BindsCurrentStore;

    public ?int $editingMenuId = null;

    public string $editingMenuHandle = '';

    /**
     * Flat ordered list of the editing menu's root items.
     *
     * @var array<int, array{id: ?int, label: string, type: string, url: string, resource_id: ?int}>
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
        if (! Gate::allows('manage-navigation')) {
            abort(403);
        }
    }

    public function getMenusProperty()
    {
        return NavigationMenu::query()->orderBy('title')->get();
    }

    public function selectMenu(int $menuId): void
    {
        $menu = NavigationMenu::query()->with('rootItems')->findOrFail($menuId);
        $this->editingMenuId = $menu->id;
        $this->editingMenuHandle = $menu->handle;

        $this->menuItems = $menu->rootItems->map(fn ($item): array => [
            'id' => $item->id,
            'label' => $item->label,
            'type' => $item->type->value,
            'url' => (string) $item->url,
            'resource_id' => $item->resource_id,
        ])->all();
    }

    public function addItem(): void
    {
        $this->resetItemForm();
        $this->editingItemIndex = null;
        $this->showItemModal = true;
    }

    public function editItem(int $index): void
    {
        $item = $this->menuItems[$index] ?? null;

        if ($item === null) {
            return;
        }

        $this->editingItemIndex = $index;
        $this->itemLabel = $item['label'];
        $this->itemType = $item['type'];
        $this->itemUrl = $item['url'];
        $this->itemResourceId = $item['resource_id'];
        $this->showItemModal = true;
    }

    public function saveItem(): void
    {
        $this->validate([
            'itemLabel' => ['required', 'string', 'max:255'],
            'itemType' => ['required', Rule::in(['link', 'page', 'collection', 'product'])],
            'itemUrl' => [Rule::requiredIf($this->itemType === 'link'), 'nullable', 'string', 'max:2048'],
            'itemResourceId' => [Rule::requiredIf($this->itemType !== 'link'), 'nullable', 'integer'],
        ]);

        $row = [
            'id' => $this->editingItemIndex !== null ? ($this->menuItems[$this->editingItemIndex]['id'] ?? null) : null,
            'label' => $this->itemLabel,
            'type' => $this->itemType,
            'url' => $this->itemType === 'link' ? $this->itemUrl : '',
            'resource_id' => $this->itemType !== 'link' ? $this->itemResourceId : null,
        ];

        if ($this->editingItemIndex !== null) {
            $this->menuItems[$this->editingItemIndex] = $row;
        } else {
            $this->menuItems[] = $row;
        }

        $this->showItemModal = false;
        $this->resetItemForm();
    }

    public function removeItem(int $index): void
    {
        unset($this->menuItems[$index]);
        $this->menuItems = array_values($this->menuItems);
    }

    public function sortItems(int $id, int $position): void
    {
        $items = $this->menuItems;
        $moved = null;

        foreach ($items as $i => $item) {
            if (($item['id'] ?? null) === $id) {
                $moved = $item;
                unset($items[$i]);
                break;
            }
        }

        if ($moved === null) {
            return;
        }

        $items = array_values($items);
        array_splice($items, $position, 0, [$moved]);
        $this->menuItems = $items;
    }

    public function saveMenu(NavigationService $navigation): void
    {
        if ($this->editingMenuId === null) {
            return;
        }

        if (! Gate::allows('manage-navigation')) {
            abort(403);
        }

        $menu = NavigationMenu::query()->findOrFail($this->editingMenuId);

        // Rebuild the root items from scratch (single-level editor).
        $menu->items()->delete();

        foreach (array_values($this->menuItems) as $position => $item) {
            $menu->items()->create([
                'parent_id' => null,
                'type' => $item['type'],
                'label' => $item['label'],
                'url' => $item['type'] === 'link' ? $item['url'] : null,
                'resource_id' => $item['type'] !== 'link' ? $item['resource_id'] : null,
                'position' => $position,
            ]);
        }

        $navigation->forget(app('current_store')->id, $menu->handle);

        $this->dispatch('toast', type: 'success', message: __('Navigation saved'));
    }

    /**
     * Resources for the item form, by the selected item type.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, label: string}>
     */
    public function getResourceOptionsProperty()
    {
        return match ($this->itemType) {
            'page' => Page::query()->orderBy('title')->get()->map(fn ($p): array => ['id' => $p->id, 'label' => $p->title]),
            'collection' => Collection::query()->orderBy('title')->get()->map(fn ($c): array => ['id' => $c->id, 'label' => $c->title]),
            'product' => Product::query()->orderBy('title')->get()->map(fn ($p): array => ['id' => $p->id, 'label' => $p->title]),
            default => collect(),
        };
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
