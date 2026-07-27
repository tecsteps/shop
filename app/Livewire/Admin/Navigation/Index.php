<?php

namespace App\Livewire\Admin\Navigation;

use App\Enums\NavigationItemType;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public ?int $selectedMenuId = null;

    public bool $showMenuForm = false;

    public string $menuTitle = '';

    public string $menuHandle = '';

    public bool $showItemForm = false;

    public ?int $editingItemId = null;

    public string $itemLabel = '';

    public string $itemType = 'link';

    public string $itemUrl = '';

    public ?int $itemResourceId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', NavigationMenu::class);

        $this->selectedMenuId ??= NavigationMenu::query()->orderBy('id')->value('id');
    }

    /**
     * Select a menu for editing (spec 03 §14).
     */
    public function selectMenu(int $menuId): void
    {
        $this->selectedMenuId = $menuId;
    }

    public function openMenuForm(): void
    {
        Gate::authorize('manage-navigation');

        $this->menuTitle = '';
        $this->menuHandle = '';
        $this->showMenuForm = true;
    }

    public function updatedMenuTitle(string $value): void
    {
        if ($this->menuHandle === '' || $this->menuHandle === Str::slug($this->menuTitle)) {
            $this->menuHandle = Str::slug($value);
        }
    }

    /**
     * Create a new navigation menu (spec 03 §14).
     */
    public function createMenu(): void
    {
        Gate::authorize('manage-navigation');

        /** @var Store $store */
        $store = app('current_store');

        $validated = $this->validate([
            'menuTitle' => ['required', 'string', 'max:255'],
            'menuHandle' => [
                'required', 'string', 'max:255',
                Rule::unique('navigation_menus', 'handle')->where('store_id', $store->id),
            ],
        ]);

        $menu = NavigationMenu::create([
            'store_id' => $store->id,
            'title' => $validated['menuTitle'],
            'handle' => $validated['menuHandle'],
        ]);

        $this->showMenuForm = false;
        $this->selectedMenuId = $menu->id;

        $this->dispatch('toast', type: 'success', message: 'Navigation saved');
    }

    /**
     * Open the item form modal in create or edit mode (spec 03 §14).
     */
    public function openItemForm(?int $itemId = null): void
    {
        Gate::authorize('manage-navigation');

        $this->editingItemId = $itemId;

        if ($itemId !== null) {
            $item = $this->selectedMenu()?->items->firstWhere('id', $itemId);

            if ($item === null) {
                return;
            }

            $this->itemLabel = $item->label;
            $this->itemType = $item->type->value;
            $this->itemUrl = (string) ($item->url ?? '');
            $this->itemResourceId = $item->resource_id;
        } else {
            $this->itemLabel = '';
            $this->itemType = 'link';
            $this->itemUrl = '';
            $this->itemResourceId = null;
        }

        $this->showItemForm = true;
    }

    /**
     * Add or update a menu item. Positions are persisted immediately; the
     * cached tree is flushed by the model hooks (spec 03 §14).
     */
    public function saveItem(): void
    {
        Gate::authorize('manage-navigation');

        $menu = $this->selectedMenu();

        if ($menu === null) {
            return;
        }

        $validated = $this->validate($this->itemRules());

        $type = NavigationItemType::from($validated['itemType']);
        $isLink = $type === NavigationItemType::Link;

        $data = [
            'type' => $type,
            'label' => $validated['itemLabel'],
            'url' => $isLink ? $validated['itemUrl'] : null,
            'resource_id' => $isLink ? null : (int) $validated['itemResourceId'],
        ];

        if ($this->editingItemId !== null) {
            $item = $menu->items->firstWhere('id', $this->editingItemId);

            if ($item === null) {
                return;
            }

            $item->update($data);
        } else {
            $menu->items()->create(array_merge($data, [
                'position' => (int) $menu->items()->max('position') + 1,
            ]));
        }

        $this->showItemForm = false;
        $this->editingItemId = null;

        $this->dispatch('toast', type: 'success', message: 'Navigation saved');
    }

    /**
     * Remove an item from the selected menu.
     */
    public function removeItem(int $itemId): void
    {
        Gate::authorize('manage-navigation');

        $item = $this->selectedMenu()?->items->firstWhere('id', $itemId);

        if ($item === null) {
            return;
        }

        $item->delete();

        $this->dispatch('toast', type: 'success', message: 'Navigation saved');
    }

    /**
     * Swap an item's position with its neighbour. Buttons are used instead
     * of drag-and-drop (spec 03 §14 note).
     */
    public function moveItem(int $itemId, string $direction): void
    {
        Gate::authorize('manage-navigation');

        $menu = $this->selectedMenu();

        if ($menu === null) {
            return;
        }

        $items = $menu->items()->orderBy('position')->orderBy('id')->get();
        $index = $items->search(fn (NavigationItem $item): bool => $item->id === $itemId);

        if ($index === false) {
            return;
        }

        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($items[$swapIndex])) {
            return;
        }

        DB::transaction(function () use ($items, $index, $swapIndex): void {
            $currentPosition = $items[$index]->position;

            $items[$index]->update(['position' => $items[$swapIndex]->position]);
            $items[$swapIndex]->update(['position' => $currentPosition]);
        });
    }

    public function render(): View
    {
        $selectedMenu = $this->selectedMenu();

        $pages = Page::query()->orderBy('title')->get(['id', 'title']);
        $collections = Collection::query()->orderBy('title')->get(['id', 'title']);
        $products = Product::query()->orderBy('title')->limit(200)->get(['id', 'title']);

        return view('livewire.admin.navigation.index', [
            'menus' => NavigationMenu::query()->withCount('items')->orderBy('id')->get(),
            'selectedMenu' => $selectedMenu,
            'items' => $selectedMenu?->items ?? collect(),
            'pages' => $pages,
            'collections' => $collections,
            'products' => $products,
            'resourceLabels' => [
                'page' => $pages->pluck('title', 'id'),
                'collection' => $collections->pluck('title', 'id'),
                'product' => $products->pluck('title', 'id'),
            ],
        ])->layout('admin.layouts.app')->title('Navigation');
    }

    /**
     * Validation rules for the item form, conditional on the item type.
     *
     * @return array<string, mixed>
     */
    private function itemRules(): array
    {
        $rules = [
            'itemLabel' => ['required', 'string', 'max:255'],
            'itemType' => ['required', Rule::in(['link', 'page', 'collection', 'product'])],
            'itemUrl' => [$this->itemType === 'link' ? 'required' : 'nullable', 'string', 'max:2048'],
            'itemResourceId' => ['nullable'],
        ];

        if ($this->itemType !== 'link') {
            /** @var Store $store */
            $store = app('current_store');

            $table = match ($this->itemType) {
                'page' => 'pages',
                'collection' => 'collections',
                'product' => 'products',
            };

            $rules['itemResourceId'] = [
                'required', 'integer',
                Rule::exists($table, 'id')->where('store_id', $store->id),
            ];
        }

        return $rules;
    }

    /**
     * The currently selected menu model.
     */
    private function selectedMenu(): ?NavigationMenu
    {
        if ($this->selectedMenuId === null) {
            return null;
        }

        return NavigationMenu::query()->find($this->selectedMenuId);
    }
}
