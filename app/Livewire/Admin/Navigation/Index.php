<?php

namespace App\Livewire\Admin\Navigation;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public string $newMenuHandle = '';

    public string $newMenuTitle = '';

    /** @var array<int, array<string, mixed>> */
    public array $newItem = [];

    public function addMenu(): void
    {
        $this->validate([
            'newMenuHandle' => 'required|string|max:100',
            'newMenuTitle' => 'required|string|max:255',
        ]);

        $store = app('current_store');
        NavigationMenu::create([
            'store_id' => $store->id,
            'handle' => $this->newMenuHandle,
            'title' => $this->newMenuTitle,
        ]);

        $this->newMenuHandle = '';
        $this->newMenuTitle = '';
    }

    public function removeMenu(int $id): void
    {
        NavigationMenu::query()->whereKey($id)->delete();
    }

    public function addItem(int $menuId): void
    {
        $entry = $this->newItem[$menuId] ?? [];
        $label = trim((string) ($entry['label'] ?? ''));
        $url = trim((string) ($entry['url'] ?? ''));

        if ($label === '') {
            return;
        }

        $position = (int) NavigationItem::query()->where('menu_id', $menuId)->max('position') + 1;

        NavigationItem::create([
            'menu_id' => $menuId,
            'type' => 'link',
            'label' => $label,
            'url' => $url,
            'position' => $position,
        ]);

        $this->newItem[$menuId] = ['label' => '', 'url' => ''];
    }

    public function removeItem(int $itemId): void
    {
        NavigationItem::query()->whereKey($itemId)->delete();
    }

    public function moveItem(int $itemId, int $delta): void
    {
        $item = NavigationItem::query()->find($itemId);
        if (! $item) {
            return;
        }

        $newPos = max(0, $item->position + $delta);
        $item->update(['position' => $newPos]);
    }

    public function render()
    {
        $store = app('current_store');
        $menus = NavigationMenu::query()->where('store_id', $store->id)->with('items')->get();

        return view('livewire.admin.navigation.index', [
            'menus' => $menus,
        ]);
    }
}
