<?php

use App\Livewire\Admin\Discounts\Form as DiscountForm;
use App\Livewire\Admin\Settings\Shipping as ShippingSettings;
use App\Livewire\Admin\Settings\Taxes as TaxSettings;
use App\Livewire\Admin\Themes\Editor as ThemeEditor;
use App\Models\Theme;
use Livewire\Livewire;

it('renders Livewire admin toggles as accessible native switches', function () {
    $context = createStoreContext();
    actingAsAdmin($context['user'], $context['store']);

    $taxes = Livewire::test(TaxSettings::class);
    expect($taxes->html())
        ->toContain('type="checkbox"')
        ->toContain('role="switch"')
        ->toContain('wire:model="pricesIncludeTax"')
        ->toContain('type="radio"')
        ->toContain('wire:model.live="mode"')
        ->not->toContain('data-flux-switch');

    $discount = Livewire::test(DiscountForm::class);
    expect($discount->html())
        ->toContain('type="checkbox"')
        ->toContain('role="switch"')
        ->toContain('wire:model="isActive"')
        ->toContain('type="radio"')
        ->toContain('wire:model.live="type"')
        ->toContain('wire:model.live="valueType"')
        ->not->toContain('data-flux-switch');

    $shipping = Livewire::test(ShippingSettings::class);
    expect($shipping->html())
        ->toContain('type="checkbox"')
        ->toContain('role="switch"')
        ->toContain('wire:model="rateActive"')
        ->not->toContain('data-flux-switch');

    expect(file_get_contents(resource_path('views/admin/settings/taxes.blade.php')))
        ->not->toContain('<flux:radio');
    expect(file_get_contents(resource_path('views/admin/discounts/form.blade.php')))
        ->not->toContain('<flux:radio');

    $theme = Theme::factory()->for($context['store'])->create();
    $editor = Livewire::test(ThemeEditor::class, ['theme' => $theme])
        ->call('selectSection', 'announcement');

    expect($editor->html())
        ->toContain('type="checkbox"')
        ->toContain('role="switch"')
        ->toContain('wire:change="updateSetting(')
        ->toContain('announcement.enabled')
        ->toContain('$event.target.checked')
        ->not->toContain('data-flux-switch');
});

it('keeps native switch state connected to Livewire behavior', function () {
    $context = createStoreContext();
    actingAsAdmin($context['user'], $context['store']);

    Livewire::test(TaxSettings::class)
        ->set('pricesIncludeTax', true)
        ->assertSet('pricesIncludeTax', true)
        ->assertSeeHtml('checked')
        ->set('mode', 'provider')
        ->assertSet('mode', 'provider')
        ->assertSee('Tax provider');

    Livewire::test(DiscountForm::class)
        ->set('isActive', false)
        ->assertSet('isActive', false)
        ->set('type', 'automatic')
        ->assertSet('type', 'automatic')
        ->set('valueType', 'free_shipping')
        ->assertSet('valueType', 'free_shipping');

    Livewire::test(ShippingSettings::class)
        ->set('rateActive', false)
        ->assertSet('rateActive', false);
});
