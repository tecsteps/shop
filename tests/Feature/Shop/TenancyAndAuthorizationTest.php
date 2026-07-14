<?php

use App\Enums\StoreUserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Scopes\StoreScope;
use App\Models\Store;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\RefundPolicy;
use App\Policies\StorePolicy;
use App\Services\CustomerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

describe('tenant resolution and global isolation', function () {
    it('resolves and caches the storefront tenant by hostname', function () {
        $context = createStoreContext();

        $this->getJson("http://{$context['domain']->hostname}/api/storefront/v1/search?q=unfindable")
            ->assertOk()
            ->assertJsonPath('pagination.total', 0);

        expect(app('current_store')->is($context['store']))->toBeTrue()
            ->and(Cache::get('store_domain:'.$context['domain']->hostname))->toBe($context['store']->id);
    });

    it('rejects unknown and suspended storefront hosts', function () {
        $this->getJson('http://missing-shop.test/api/storefront/v1/search?q=anything')
            ->assertNotFound();

        $context = createStoreContext(storeAttributes: ['status' => 'suspended']);
        $this->getJson("http://{$context['domain']->hostname}/api/storefront/v1/search?q=anything")
            ->assertStatus(503);
    });

    it('scopes reads creates and direct identifiers to the current store', function () {
        $storeA = createStoreContext()['store'];
        Product::factory()->count(3)->for($storeA)->create();
        Order::factory()->count(2)->for($storeA)->create(['customer_id' => null]);

        $storeB = Store::factory()->create();
        $foreign = Product::factory()->count(5)->for($storeB)->create()->first();
        Order::factory()->count(4)->for($storeB)->create(['customer_id' => null]);

        bindStore($storeA);
        $created = Product::query()->create([
            'title' => 'Scoped Product',
            'handle' => 'scoped-product',
            'status' => 'draft',
            'tags' => [],
        ]);

        expect(Product::query()->count())->toBe(4)
            ->and(Order::query()->count())->toBe(2)
            ->and($created->store_id)->toBe($storeA->id)
            ->and(Product::query()->find($foreign->id))->toBeNull()
            ->and(Product::withoutGlobalScope(StoreScope::class)->count())->toBe(9);
    });

    it('allows one customer email per store, not globally', function () {
        $storeA = createStoreContext()['store'];
        $storeB = createStoreContext()['store'];
        $service = app(CustomerService::class);

        $customerA = $service->register($storeA, [
            'name' => 'Same Buyer',
            'email' => 'same@example.test',
            'password' => 'correct-horse',
        ]);
        $customerB = $service->register($storeB, [
            'name' => 'Same Buyer',
            'email' => 'same@example.test',
            'password' => 'correct-horse',
        ]);

        expect($customerA->store_id)->not->toBe($customerB->store_id)
            ->and(Customer::withoutGlobalScopes()->where('email', 'same@example.test')->count())->toBe(2);

        $service->register($storeA, [
            'name' => 'Duplicate',
            'email' => 'same@example.test',
            'password' => 'correct-horse',
        ]);
    })->throws(ValidationException::class);
});

describe('role policies', function () {
    it('returns the typed role for a store and null elsewhere', function () {
        $context = createStoreContext(StoreUserRole::Staff);
        $unrelated = Store::factory()->create();

        expect($context['user']->roleForStore($context['store']))->toBe(StoreUserRole::Staff)
            ->and($context['user']->roleForStore($unrelated))->toBeNull();
    });

    it('enforces product permissions for every store role', function (string $role, bool $create, bool $delete) {
        $context = createStoreContext($role);
        $product = Product::factory()->for($context['store'])->create();
        $policy = new ProductPolicy;

        expect($policy->view($context['user'], $product))->toBeTrue()
            ->and($policy->create($context['user']))->toBe($create)
            ->and($policy->update($context['user'], $product))->toBe($create)
            ->and($policy->delete($context['user'], $product))->toBe($delete);
    })->with([
        'owner' => ['owner', true, true],
        'admin' => ['admin', true, true],
        'staff' => ['staff', true, false],
        'support' => ['support', false, false],
    ]);

    it('enforces order refund and fulfillment permissions for every store role', function (string $role, bool $mutate, bool $refund) {
        $context = createStoreContext($role);
        $order = Order::factory()->for($context['store'])->create(['customer_id' => null]);
        $orders = new OrderPolicy;
        $refunds = new RefundPolicy;

        expect($orders->view($context['user'], $order))->toBeTrue()
            ->and($orders->update($context['user'], $order))->toBe($mutate)
            ->and($orders->fulfill($context['user'], $order))->toBe($mutate)
            ->and($orders->refund($context['user'], $order))->toBe($refund)
            ->and($refunds->create($context['user'], $order))->toBe($refund);
    })->with([
        'owner' => ['owner', true, true],
        'admin' => ['admin', true, true],
        'staff' => ['staff', true, false],
        'support read-only' => ['support', false, false],
    ]);

    it('restricts settings staff management and store deletion', function (string $role, bool $settings, bool $delete) {
        $context = createStoreContext($role);
        $policy = new StorePolicy;

        expect($policy->view($context['user'], $context['store']))->toBeTrue()
            ->and($policy->update($context['user'], $context['store']))->toBe($settings)
            ->and($policy->manageStaff($context['user'], $context['store']))->toBe($settings)
            ->and($policy->delete($context['user'], $context['store']))->toBe($delete);
    })->with([
        'owner' => ['owner', true, true],
        'admin' => ['admin', true, false],
        'staff' => ['staff', false, false],
        'support' => ['support', false, false],
    ]);

    it('never authorizes a resource from another tenant', function () {
        $context = createStoreContext('owner');
        $other = Store::factory()->create();
        $foreignProduct = Product::factory()->for($other)->create();

        expect((new ProductPolicy)->update($context['user'], $foreignProduct))->toBeFalse();
    });
});

describe('Sanctum administration tokens', function () {
    it('persists abilities and authenticates a bearer token', function () {
        $context = createStoreContext();
        $token = $context['user']->createToken('qa', [
            'read-products',
            'write-products',
            'store:'.$context['store']->id,
        ]);

        expect($token->accessToken->abilities)->toBe([
            'read-products',
            'write-products',
            'store:'.$context['store']->id,
        ])
            ->and($token->plainTextToken)->toContain('|');

        $this->withToken($token->plainTextToken)
            ->getJson("/api/admin/v1/stores/{$context['store']->id}/products")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total']]);
    });

    it('rejects missing invalid revoked and cross-store tokens', function () {
        $context = createStoreContext();
        $otherStore = Store::factory()->create();

        $this->getJson("/api/admin/v1/stores/{$context['store']->id}/products")->assertUnauthorized();
        $this->withToken('definitely-invalid')->getJson("/api/admin/v1/stores/{$context['store']->id}/products")->assertUnauthorized();

        $token = $context['user']->createToken('revoked', [
            'read-products',
            'store:'.$context['store']->id,
        ]);
        $plain = $token->plainTextToken;
        $token->accessToken->delete();
        $this->withToken($plain)->getJson("/api/admin/v1/stores/{$context['store']->id}/products")->assertUnauthorized();

        $valid = $context['user']->createToken('valid', [
            'read-products',
            'store:'.$context['store']->id,
        ]);
        $this->withToken($valid->plainTextToken)
            ->getJson("/api/admin/v1/stores/{$otherStore->id}/products")
            ->assertForbidden();
    });

    it('enforces token abilities on product mutations', function () {
        $context = createStoreContext();
        $readOnly = $context['user']->createToken('reader', [
            'read-products',
            'store:'.$context['store']->id,
        ]);

        $this->withToken($readOnly->plainTextToken)
            ->postJson("/api/admin/v1/stores/{$context['store']->id}/products", [
                'title' => 'Forbidden Product',
                'variant' => ['price_amount' => 1000],
            ])
            ->assertForbidden();
    });
});
