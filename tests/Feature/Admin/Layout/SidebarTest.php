<?php

use App\Livewire\Admin\Layout\Sidebar;
use Livewire\Livewire;

test('sidebar component can be rendered', function () {
    Livewire::test(Sidebar::class)
        ->assertOk()
        ->assertSee('Shop Admin')
        ->assertSee('Dashboard');
});

test('sidebar displays all navigation groups', function () {
    Livewire::test(Sidebar::class)
        ->assertSee('Products')
        ->assertSee('Collections')
        ->assertSee('Inventory')
        ->assertSee('Orders')
        ->assertSee('Customers')
        ->assertSee('Discounts')
        ->assertSee('Pages')
        ->assertSee('Navigation')
        ->assertSee('Themes')
        ->assertSee('Analytics')
        ->assertSee('Settings')
        ->assertSee('Apps')
        ->assertSee('Developers');
});

test('sidebar toggle action toggles collapsed state', function () {
    Livewire::test(Sidebar::class)
        ->assertSet('collapsed', false)
        ->call('toggle')
        ->assertSet('collapsed', true)
        ->call('toggle')
        ->assertSet('collapsed', false);
});

test('sidebar has computed currentRoute property', function () {
    $component = Livewire::test(Sidebar::class);

    expect($component->instance()->currentRoute)->toBeNull();
});
