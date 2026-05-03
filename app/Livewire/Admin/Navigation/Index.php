<?php

namespace App\Livewire\Admin\Navigation;

use App\Enums\NavigationItemType;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    use UsesAdminStore;

    public ?int $menuId = null;

    public string $label = '';

    public string $url = '/';

    public string $type = 'link';

    public function addItem(): void
    {
        $validated = $this->validate([
            'menuId' => ['required', 'integer', 'exists:navigation_menus,id'],
            'label' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:500'],
            'type' => ['required', Rule::in(array_map(fn (NavigationItemType $type): string => $type->value, NavigationItemType::cases()))],
        ]);

        $menu = NavigationMenu::query()->whereKey($validated['menuId'])->firstOrFail();

        $menu->items()->create([
            'label' => $validated['label'],
            'url' => $validated['url'],
            'type' => $validated['type'],
            'position' => $menu->items()->max('position') + 1,
        ]);

        $this->reset('label');
        $this->url = '/';
        $this->notify('Navigation item added.');
    }

    public function deleteItem(int $itemId): void
    {
        NavigationItem::query()->whereKey($itemId)->firstOrFail()->delete();
        $this->notify('Navigation item deleted.');
    }

    public function render(): View
    {
        $menus = NavigationMenu::query()->with(['items' => fn ($query) => $query->orderBy('position')])->orderBy('title')->get();
        $this->menuId ??= $menus->first()?->id;

        return view('livewire.admin.navigation.index', [
            'menus' => $menus,
            'types' => NavigationItemType::cases(),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Navigation',
        ]);
    }
}
