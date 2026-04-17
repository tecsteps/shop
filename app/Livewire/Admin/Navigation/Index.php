<?php

namespace App\Livewire\Admin\Navigation;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public string $itemLabel = '';

    public string $itemUrl = '';

    public ?int $menuId = null;

    public function addItem(): void
    {
        if (! $this->menuId || $this->itemLabel === '') {
            return;
        }

        NavigationItem::create([
            'menu_id' => $this->menuId,
            'type' => 'link',
            'label' => $this->itemLabel,
            'url' => $this->itemUrl ?: '/',
            'position' => NavigationItem::where('menu_id', $this->menuId)->count(),
        ]);

        $this->itemLabel = '';
        $this->itemUrl = '';
    }

    public function deleteItem(int $id): void
    {
        NavigationItem::where('id', $id)->delete();
    }

    public function render()
    {
        $menus = NavigationMenu::query()->with('items')->get();

        if (! $this->menuId && $menus->isNotEmpty()) {
            $this->menuId = $menus->first()->id;
        }

        return view('livewire.admin.navigation.index', compact('menus'))->title('Navigation');
    }
}
