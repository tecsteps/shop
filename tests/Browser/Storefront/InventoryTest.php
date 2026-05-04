<?php

use App\Enums\InventoryPolicy;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Store;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Playwright::setHost('shop.test');

    $this->seed(DatabaseSeeder::class);
});

afterEach(function (): void {
    Playwright::setHost(null);
});

function storefrontInventoryHost(): array
{
    return ['host' => 'shop.test'];
}

function storefrontInventoryStore(): Store
{
    return Store::query()->where('handle', 'acme-fashion')->firstOrFail();
}

/**
 * @param  array<string, string>  $options
 */
function storefrontInventoryVariant(string $handle, array $options): ProductVariant
{
    $store = storefrontInventoryStore();
    $product = Product::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', $handle)
        ->firstOrFail();

    return ProductVariant::withoutGlobalScopes()
        ->with(['optionValues.option'])
        ->where('product_id', $product->getKey())
        ->get()
        ->first(function (ProductVariant $variant) use ($options): bool {
            $variantOptions = $variant->optionValues
                ->mapWithKeys(fn (ProductOptionValue $value): array => [$value->option->name => $value->value])
                ->all();

            return $variantOptions === $options;
        }) ?? throw new RuntimeException('Variant fixture not found.');
}

test('blocks add to cart for out of stock deny policy product', function (): void {
    visit('/products/limited-edition-sneakers', storefrontInventoryHost())
        ->assertSee('Limited Edition Sneakers')
        ->assertSee('Out of stock')
        ->assertSee('Sold out')
        ->assertButtonDisabled('button:has-text("Sold out")')
        ->assertDontSee('Add to cart')
        ->assertNoJavaScriptErrors();
});

test('allows add to cart for out of stock continue policy product', function (): void {
    visit('/products/backorder-denim-jacket', storefrontInventoryHost())
        ->assertSee('Backorder Denim Jacket')
        ->assertSee('Available on backorder')
        ->click('Add to cart')
        ->wait(1)
        ->navigate('/cart')
        ->wait(1)
        ->assertSee('Backorder Denim Jacket')
        ->assertNoJavaScriptErrors();
});

test('shows correct stock status for in stock product', function (): void {
    visit('/products/classic-cotton-t-shirt', storefrontInventoryHost())
        ->assertSee('Classic Cotton T-Shirt')
        ->assertSee('In stock')
        ->assertButtonEnabled('button:has-text("Add to cart")')
        ->assertDontSee('Sold out')
        ->assertDontSee('Available on backorder')
        ->assertNoJavaScriptErrors();
});

test('prevents adding more than available stock for deny policy product', function (): void {
    $variant = storefrontInventoryVariant('classic-cotton-t-shirt', [
        'Size' => 'M',
        'Color' => 'Black',
    ]);

    InventoryItem::withoutGlobalScopes()
        ->where('variant_id', $variant->getKey())
        ->firstOrFail()
        ->forceFill([
            'quantity_on_hand' => 2,
            'quantity_reserved' => 0,
            'policy' => InventoryPolicy::Deny,
        ])
        ->save();

    visit('/products/classic-cotton-t-shirt', storefrontInventoryHost())
        ->click('M')
        ->wait(1)
        ->click('Black')
        ->wait(1)
        ->assertSee('Only 2 left in stock')
        ->click('Add to cart')
        ->wait(1)
        ->navigate('/cart')
        ->wait(1)
        ->click('main button[aria-label="Increase Classic Cotton T-Shirt quantity"]')
        ->wait(1)
        ->assertSee('2 items')
        ->click('main button[aria-label="Increase Classic Cotton T-Shirt quantity"]')
        ->wait(1)
        ->assertSee('Only 2 units are available')
        ->assertSee('2 items')
        ->assertNoJavaScriptErrors();
});
