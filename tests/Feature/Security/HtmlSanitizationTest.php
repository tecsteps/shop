<?php

use App\Actions\SanitizeHtml;
use App\Enums\PageStatus;
use App\Enums\StoreUserRole;
use App\Livewire\Admin\Collections\Form as CollectionForm;
use App\Livewire\Admin\Pages\Form as PageForm;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Models\Collection;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

function htmlSanitizationAdminUser(Store $store): User
{
    $user = User::factory()->create();
    $user->stores()->attach($store->getKey(), [
        'role' => StoreUserRole::Admin->value,
    ]);

    return $user;
}

function htmlSanitizationUnsafeHtml(string $heading): string
{
    return implode('', [
        "<h2>{$heading}</h2>",
        '<p onclick="evil()">Intro <strong>safe</strong> <em>copy</em> <u>underlined</u> <a href="/size" target="_blank" onclick="evil()">size</a></p>',
        '<ul><li>One</li></ul>',
        '<blockquote cite="https://example.test">Quoted</blockquote>',
        '<table data-extra="1"><tbody><tr><th scope="col">Fit</th><td style="color:red">Regular</td></tr></tbody></table>',
        '<img src="/images/tee.jpg" alt="Tee" onerror="evil()">',
        '<script>alert(1)</script>',
        '<iframe src="https://evil.test"></iframe>',
        '<section>Unwrapped</section>',
        '<span style="color:red">Plain span</span>',
        '<p></p>',
        '<img src="javascript:alert(1)" alt="bad">',
        '<a href="javascript:alert(1)">unsafe link</a>',
    ]);
}

function expectSanitizedRichHtml(string $html, string $heading): void
{
    expect($html)
        ->toContain("<h2>{$heading}</h2>")
        ->toContain('<strong>safe</strong>')
        ->toContain('<em>copy</em>')
        ->toContain('<u>underlined</u>')
        ->toContain('<a href="/size">size</a>')
        ->toContain('<ul><li>One</li></ul>')
        ->toContain('<blockquote>Quoted</blockquote>')
        ->toContain('<th>Fit</th>')
        ->toContain('<td>Regular</td>')
        ->toContain('src="/images/tee.jpg"')
        ->toContain('alt="Tee"')
        ->toContain('Unwrapped')
        ->toContain('<span>Plain span</span>')
        ->not->toContain('<script')
        ->not->toContain('alert(1)')
        ->not->toContain('onclick')
        ->not->toContain('onerror')
        ->not->toContain('style=')
        ->not->toContain('target=')
        ->not->toContain('cite=')
        ->not->toContain('scope=')
        ->not->toContain('data-extra')
        ->not->toContain('<iframe')
        ->not->toContain('<section')
        ->not->toContain('javascript:')
        ->not->toContain('<p></p>');
}

test('sanitize html action applies the security allowlist', function (): void {
    $sanitized = app(SanitizeHtml::class)(htmlSanitizationUnsafeHtml('Allowlist Details'));

    expect($sanitized)->toBeString();
    expectSanitizedRichHtml($sanitized, 'Allowlist Details');
});

test('admin product form sanitizes description html on create and update', function (): void {
    $store = Store::factory()->create();
    $user = htmlSanitizationAdminUser($store);

    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('title', 'Sanitized UI Product')
        ->set('handle', 'sanitized-ui-product')
        ->set('descriptionHtml', htmlSanitizationUnsafeHtml('Product UI Create Details'))
        ->set('variants.0.price', '19.99')
        ->set('variants.0.quantity', 5)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'sanitized-ui-product')
        ->firstOrFail();

    expectSanitizedRichHtml((string) $product->description_html, 'Product UI Create Details');

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['product' => $product])
        ->set('descriptionHtml', htmlSanitizationUnsafeHtml('Product UI Update Details'))
        ->call('save')
        ->assertHasNoErrors();

    expectSanitizedRichHtml((string) $product->refresh()->description_html, 'Product UI Update Details');
});

test('admin page form sanitizes body html on create and update', function (): void {
    $store = Store::factory()->create();
    $user = htmlSanitizationAdminUser($store);

    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(PageForm::class)
        ->set('title', 'Sanitized UI Page')
        ->set('handle', 'sanitized-ui-page')
        ->set('bodyHtml', htmlSanitizationUnsafeHtml('Page UI Create Details'))
        ->set('status', PageStatus::Published->value)
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'sanitized-ui-page')
        ->firstOrFail();

    expectSanitizedRichHtml((string) $page->body_html, 'Page UI Create Details');

    Livewire::actingAs($user)
        ->test(PageForm::class, ['page' => $page])
        ->set('bodyHtml', htmlSanitizationUnsafeHtml('Page UI Update Details'))
        ->call('save')
        ->assertHasNoErrors();

    expectSanitizedRichHtml((string) $page->refresh()->body_html, 'Page UI Update Details');
});

test('admin collection form sanitizes description html on create and update', function (): void {
    $store = Store::factory()->create();
    $user = htmlSanitizationAdminUser($store);

    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(CollectionForm::class)
        ->set('title', 'Sanitized UI Collection')
        ->set('handle', 'sanitized-ui-collection')
        ->set('descriptionHtml', htmlSanitizationUnsafeHtml('Collection UI Create Details'))
        ->call('save')
        ->assertHasNoErrors();

    $collection = Collection::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'sanitized-ui-collection')
        ->firstOrFail();

    expectSanitizedRichHtml((string) $collection->description_html, 'Collection UI Create Details');

    Livewire::actingAs($user)
        ->test(CollectionForm::class, ['collection' => $collection])
        ->set('descriptionHtml', htmlSanitizationUnsafeHtml('Collection UI Update Details'))
        ->call('save')
        ->assertHasNoErrors();

    expectSanitizedRichHtml((string) $collection->refresh()->description_html, 'Collection UI Update Details');
});

test('admin product api sanitizes description html on create and update', function (): void {
    $store = Store::factory()->create();
    $user = htmlSanitizationAdminUser($store);

    $createResponse = $this->withToken(adminApiBearerToken($store, ['write-products'], $user))
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/products", [
            'title' => 'Sanitized API Product',
            'handle' => 'sanitized-api-product',
            'description_html' => htmlSanitizationUnsafeHtml('Product API Create Details'),
            'status' => 'active',
            'variants' => [
                [
                    'sku' => 'SANITIZED-API-1',
                    'price_amount' => 1999,
                    'is_default' => true,
                ],
            ],
        ])
        ->assertCreated();

    expectSanitizedRichHtml($createResponse->json('data.description_html'), 'Product API Create Details');

    $product = Product::withoutGlobalScopes()->findOrFail($createResponse->json('data.id'));

    expectSanitizedRichHtml((string) $product->description_html, 'Product API Create Details');

    $updateResponse = $this->withToken(adminApiBearerToken($store, ['write-products'], $user))
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/products/{$product->getKey()}", [
            'description_html' => htmlSanitizationUnsafeHtml('Product API Update Details'),
        ])
        ->assertOk();

    expectSanitizedRichHtml($updateResponse->json('data.description_html'), 'Product API Update Details');
    expectSanitizedRichHtml((string) $product->refresh()->description_html, 'Product API Update Details');
});

test('admin page api sanitizes body html on create and update', function (): void {
    $store = Store::factory()->create();
    $user = htmlSanitizationAdminUser($store);

    $createResponse = $this->withToken(adminApiBearerToken($store, ['write-content'], $user))
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/pages", [
            'title' => 'Sanitized API Page',
            'body_html' => htmlSanitizationUnsafeHtml('Page API Create Details'),
            'status' => 'published',
        ])
        ->assertCreated();

    expectSanitizedRichHtml($createResponse->json('data.body_html'), 'Page API Create Details');

    $page = Page::withoutGlobalScopes()->findOrFail($createResponse->json('data.id'));

    expectSanitizedRichHtml((string) $page->body_html, 'Page API Create Details');

    $updateResponse = $this->withToken(adminApiBearerToken($store, ['write-content'], $user))
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/pages/{$page->getKey()}", [
            'body_html' => htmlSanitizationUnsafeHtml('Page API Update Details'),
        ])
        ->assertOk();

    expectSanitizedRichHtml($updateResponse->json('data.body_html'), 'Page API Update Details');
    expectSanitizedRichHtml((string) $page->refresh()->body_html, 'Page API Update Details');
});

test('admin collection api sanitizes description html on create and update', function (): void {
    $store = Store::factory()->create();
    $user = htmlSanitizationAdminUser($store);

    $createResponse = $this->withToken(adminApiBearerToken($store, ['write-collections'], $user))
        ->postJson("/api/admin/v1/stores/{$store->getKey()}/collections", [
            'title' => 'Sanitized API Collection',
            'description_html' => htmlSanitizationUnsafeHtml('Collection API Create Details'),
            'type' => 'manual',
            'status' => 'active',
        ])
        ->assertCreated();

    expectSanitizedRichHtml($createResponse->json('data.description_html'), 'Collection API Create Details');

    $collection = Collection::withoutGlobalScopes()->findOrFail($createResponse->json('data.id'));

    expectSanitizedRichHtml((string) $collection->description_html, 'Collection API Create Details');

    $updateResponse = $this->withToken(adminApiBearerToken($store, ['write-collections'], $user))
        ->putJson("/api/admin/v1/stores/{$store->getKey()}/collections/{$collection->getKey()}", [
            'description_html' => htmlSanitizationUnsafeHtml('Collection API Update Details'),
        ])
        ->assertOk();

    expectSanitizedRichHtml($updateResponse->json('data.description_html'), 'Collection API Update Details');
    expectSanitizedRichHtml((string) $collection->refresh()->description_html, 'Collection API Update Details');
});

test('admin Livewire product and collection forms ignore cross store pivot ids', function (): void {
    $store = Store::factory()->create();
    $otherStore = Store::factory()->create();
    $user = htmlSanitizationAdminUser($store);
    $otherCollection = Collection::factory()->create(['store_id' => $otherStore->getKey()]);
    $otherProduct = Product::factory()->withDefaultVariant()->create(['store_id' => $otherStore->getKey()]);

    app()->instance('current_store', $store);

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('title', 'Tenant Safe Product')
        ->set('handle', 'tenant-safe-product')
        ->set('collectionIds', [$otherCollection->getKey()])
        ->set('variants.0.price', '19.99')
        ->set('variants.0.quantity', 5)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'tenant-safe-product')
        ->firstOrFail();

    expect($product->collections()->withoutGlobalScopes()->count())->toBe(0);

    Livewire::actingAs($user)
        ->test(CollectionForm::class)
        ->set('title', 'Tenant Safe Collection')
        ->set('handle', 'tenant-safe-collection')
        ->set('assignedProductIds', [$otherProduct->getKey()])
        ->call('save')
        ->assertHasNoErrors();

    $collection = Collection::withoutGlobalScopes()
        ->where('store_id', $store->getKey())
        ->where('handle', 'tenant-safe-collection')
        ->firstOrFail();

    expect($collection->products()->withoutGlobalScopes()->count())->toBe(0);
});
