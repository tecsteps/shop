<?php

namespace App\Livewire\Admin\Navigation;

use App\Livewire\Admin\AdminComponent;
use App\Models\Collection as ProductCollection;
use App\Models\NavigationMenu;
use App\Models\Page;
use App\Models\Product;
use App\Support\SafeUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

class Index extends AdminComponent
{
    public ?NavigationMenu $editingMenu = null;

    /** @var list<array<string, mixed>> */
    public array $menuItems = [];

    /** @var array<string, mixed>|null */
    public ?array $editingItem = null;

    public ?int $editingItemIndex = null;

    public string $itemLabel = '';

    public string $itemType = 'link';

    public string $itemUrl = '';

    public ?int $itemResourceId = null;

    public function mount(): void
    {
        $this->authorizeNavigation();
        $first = NavigationMenu::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->orderBy('id')->first();
        if ($first) {
            $this->loadMenu($first);
        }
    }

    public function selectMenu(int $menuId): void
    {
        $this->authorizeNavigation();
        $this->loadMenu($this->menu($menuId));
    }

    public function addItem(): void
    {
        $this->authorizeNavigation();
        abort_unless($this->editingMenu, 422, 'Select a menu first.');
        $this->editingItem = null;
        $this->editingItemIndex = null;
        $this->itemLabel = '';
        $this->itemType = 'link';
        $this->itemUrl = '';
        $this->itemResourceId = null;
        $this->resetValidation();
        $this->dispatch('modal-show', name: 'item-form');
    }

    public function editItem(int $index): void
    {
        $this->authorizeNavigation();
        abort_unless(array_key_exists($index, $this->menuItems), 404);
        $this->editingItemIndex = $index;
        $this->editingItem = $this->menuItems[$index];
        $this->itemLabel = (string) $this->editingItem['label'];
        $this->itemType = (string) $this->editingItem['type'];
        $this->itemUrl = (string) ($this->editingItem['url'] ?? '');
        $this->itemResourceId = filled($this->editingItem['resource_id'] ?? null) ? (int) $this->editingItem['resource_id'] : null;
        $this->resetValidation();
        $this->dispatch('modal-show', name: 'item-form');
    }

    public function saveItem(): void
    {
        $this->authorizeNavigation();
        abort_unless($this->editingMenu, 422, 'Select a menu first.');
        $data = $this->validate([
            'itemLabel' => ['required', 'string', 'max:255'],
            'itemType' => ['required', Rule::in(['link', 'page', 'collection', 'product'])],
            'itemUrl' => [
                'required_if:itemType,link', 'nullable', 'string', 'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! SafeUrl::isAllowed($value)) {
                        $fail('The link must be a relative, HTTP, or HTTPS URL.');
                    }
                },
            ],
            'itemResourceId' => ['required_unless:itemType,link', 'nullable', 'integer', 'min:1'],
        ]);
        if ($data['itemType'] !== 'link') {
            $this->assertResourceBelongsToStore($data['itemType'], (int) $data['itemResourceId']);
        }
        $item = [
            'label' => trim($data['itemLabel']),
            'type' => $data['itemType'],
            'url' => $data['itemType'] === 'link' ? SafeUrl::normalize($data['itemUrl']) : null,
            'resource_id' => $data['itemType'] === 'link' ? null : (int) $data['itemResourceId'],
        ];
        if ($this->editingItemIndex === null) {
            $this->menuItems[] = $item;
        } else {
            $this->menuItems[$this->editingItemIndex] = $item;
        }
        $this->editingItem = null;
        $this->editingItemIndex = null;
        $this->dispatch('modal-close', name: 'item-form');
    }

    public function removeItem(int $index): void
    {
        $this->authorizeNavigation();
        abort_unless(array_key_exists($index, $this->menuItems), 404);
        unset($this->menuItems[$index]);
        $this->menuItems = array_values($this->menuItems);
    }

    /** @param list<int> $order */
    public function reorderItems(array $order): void
    {
        $this->authorizeNavigation();
        abort_unless(count($order) === count($this->menuItems), 422);
        $old = $this->menuItems;
        $this->menuItems = collect($order)->map(function (int $index) use ($old): array {
            abort_unless(array_key_exists($index, $old), 422);

            return $old[$index];
        })->values()->all();
    }

    public function saveMenu(): void
    {
        $this->authorizeNavigation();
        abort_unless($this->editingMenu, 422, 'Select a menu first.');
        $this->editingMenu = $this->menu($this->editingMenu->id);
        foreach ($this->menuItems as $item) {
            abort_unless(in_array($item['type'], ['link', 'page', 'collection', 'product'], true), 422);
            abort_if($item['type'] === 'link' && ! SafeUrl::isAllowed($item['url'] ?? null), 422, 'A navigation link is unsafe.');
            if ($item['type'] !== 'link') {
                $this->assertResourceBelongsToStore($item['type'], (int) $item['resource_id']);
            }
        }
        DB::transaction(function (): void {
            $this->editingMenu->items()->delete();
            foreach ($this->menuItems as $position => $item) {
                $this->editingMenu->items()->create([...$item, 'position' => $position]);
            }
        });
        $this->loadMenu($this->editingMenu->fresh());
        $this->toast('Navigation saved');
    }

    #[Computed]
    public function menus(): mixed
    {
        return NavigationMenu::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->withCount('items')->orderBy('title')->get();
    }

    #[Computed]
    public function resources(): array
    {
        $storeId = $this->currentStore()->id;

        return [
            'page' => Page::withoutGlobalScopes()->where('store_id', $storeId)->orderBy('title')->get(['id', 'title'])->map(fn (Page $item): array => ['id' => $item->id, 'label' => $item->title])->all(),
            'collection' => ProductCollection::withoutGlobalScopes()->where('store_id', $storeId)->orderBy('title')->get(['id', 'title'])->map(fn (ProductCollection $item): array => ['id' => $item->id, 'label' => $item->title])->all(),
            'product' => Product::withoutGlobalScopes()->where('store_id', $storeId)->orderBy('title')->get(['id', 'title'])->map(fn (Product $item): array => ['id' => $item->id, 'label' => $item->title])->all(),
        ];
    }

    public function render(): View
    {
        return $this->admin(view('admin.navigation.index'), 'Navigation', [['label' => 'Navigation']]);
    }

    private function authorizeNavigation(): void
    {
        $this->requireRoles(['owner', 'admin']);
        $this->authorizeAction('update', $this->currentStore());
    }

    private function menu(int $id): NavigationMenu
    {
        return NavigationMenu::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->findOrFail($id);
    }

    private function loadMenu(NavigationMenu $menu): void
    {
        $this->editingMenu = $menu->load('items');
        $this->menuItems = $this->editingMenu->items->map(fn ($item): array => [
            'label' => $item->label,
            'type' => (string) $this->enumValue($item->type),
            'url' => $item->url,
            'resource_id' => $item->resource_id,
        ])->values()->all();
    }

    private function assertResourceBelongsToStore(string $type, int $id): void
    {
        $class = match ($type) {
            'page' => Page::class,
            'collection' => ProductCollection::class,
            'product' => Product::class,
            default => abort(422),
        };
        abort_unless($class::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->whereKey($id)->exists(), 422, 'The selected resource is unavailable.');
    }
}
