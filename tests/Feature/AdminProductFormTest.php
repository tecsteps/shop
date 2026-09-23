<?php

use App\Livewire\Admin\Products\Form;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use Livewire\Livewire;

function productFormOwner(\App\Models\Store $store): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->id, ['role' => 'owner']);
    app()->instance('current_store', $store);

    return $user;
}

it('creates all option combinations and assigns the configured default variant values', function () {
    $store = shopStore();
    $user = productFormOwner($store);

    Livewire::actingAs($user)->test(Form::class)
        ->set('title', 'Everyday Cotton Tee')
        ->set('handle', 'everyday-cotton-tee')
        ->set('priceAmount', 3200)
        ->set('compareAtAmount', 4000)
        ->set('quantityOnHand', 12)
        ->set('sku', 'TEE-DEFAULT')
        ->set('options', [
            ['name' => 'Size', 'values' => 'S, M'],
            ['name' => 'Color', 'values' => 'Black, White'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->where('handle', 'everyday-cotton-tee')->with('variants.inventoryItem', 'variants.optionValues.option')->firstOrFail();

    expect($product->variants)->toHaveCount(4)
        ->and($product->options)->toHaveCount(2)
        ->and($product->variants->firstWhere('is_default', true)->sku)->toBe('TEE-DEFAULT')
        ->and($product->variants->first()->price_amount)->toBe(3200)
        ->and($product->variants->first()->inventoryItem->quantity_on_hand)->toBe(12)
        ->and($product->variants->where('is_default', false)->every(fn ($variant): bool => $variant->inventoryItem->quantity_on_hand === 0))->toBeTrue();
});

it('rejects a SKU already used by another product in the current store', function () {
    $store = shopStore();
    $user = productFormOwner($store);
    $products = app(ProductService::class);
    $first = $products->create($store, ['title' => 'First item', 'variants' => [['sku' => 'STORE-SKU-1', 'price_amount' => 2500]]]);
    $products->create($store, ['title' => 'Second item', 'variants' => [['sku' => 'STORE-SKU-2', 'price_amount' => 2700]]]);

    Livewire::actingAs($user)->test(Form::class, ['product' => $first->id])
        ->set('variants.0.sku', 'STORE-SKU-2')
        ->call('save')
        ->assertHasErrors('variants');

    expect($first->variants()->firstOrFail()->sku)->toBe('STORE-SKU-1');
});

it('rejects product option values without at least one non-empty value', function () {
    $store = shopStore();
    $user = productFormOwner($store);

    Livewire::actingAs($user)->test(Form::class)
        ->set('title', 'Invalid Option Product')
        ->set('priceAmount', 1000)
        ->set('options', [['name' => 'Size', 'values' => ', ,']])
        ->call('save')
        ->assertHasErrors('options.0.values');

    expect(Product::query()->where('title', 'Invalid Option Product')->exists())->toBeFalse();
});

it('preserves allowed product formatting, removes unsafe markup, and validates compare-at pricing', function () {
    $store = shopStore();
    $user = productFormOwner($store);
    $form = Livewire::actingAs($user)->test(Form::class)
        ->set('title', 'Formatted Product')
        ->set('priceAmount', 2500)
        ->set('compareAtAmount', 2500)
        ->set('descriptionHtml', '<p>Soft <strong>cotton</strong><script>alert(1)</script><img src="x" onerror="alert(2)"></p>')
        ->call('save')
        ->assertHasErrors('compareAtAmount');

    expect(Product::query()->where('title', 'Formatted Product')->exists())->toBeFalse();

    $form->set('compareAtAmount', 3000)->call('save')->assertHasNoErrors();
    $product = Product::query()->where('title', 'Formatted Product')->firstOrFail();

    expect($product->description_html)->toContain('<p>')
        ->and($product->description_html)->toContain('<strong>cotton</strong>')
        ->and($product->description_html)->not->toContain('<script')
        ->and($product->description_html)->not->toContain('onerror');
});
