<?php

use App\Http\Controllers\Api\Admin\AnalyticsController;
use App\Http\Controllers\Api\Admin\CollectionController;
use App\Http\Controllers\Api\Admin\ContentController;
use App\Http\Controllers\Api\Admin\DiscountController;
use App\Http\Controllers\Api\Admin\MediaController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Requests\CreateFulfillmentRequest;
use App\Http\Requests\CreateRefundRequest;
use App\Http\Requests\InviteStaffRequest;
use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\StoreDiscountRequest;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\StoreShippingRateRequest;
use App\Http\Requests\StoreShippingZoneRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UpdateStoreSettingsRequest;
use App\Http\Requests\UpdateTaxSettingsRequest;
use App\Jobs\GenerateOrderExport;
use App\Livewire\Admin\Auth\Login as AdminLogin;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Storefront\Account\Auth\Login as CustomerLogin;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderExport;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/** @param class-string<FormRequest> $requestClass */
function securityFormRequest(string $requestClass, User $user, ?Route $route = null): FormRequest
{
    $request = $requestClass::create('/authorization-check', 'POST');
    $request->setUserResolver(fn (): User => $user);
    if ($route !== null) {
        $request->setRouteResolver(fn (): Route => $route);
    }

    return $request;
}

function securityRoute(string $uri, string $parameter, int $value): Route
{
    $request = Request::create(str_replace('{'.$parameter.'}', (string) $value, $uri), 'POST');
    $route = new Route(['POST'], $uri, fn () => null);
    $route->bind($request);

    return $route;
}

afterEach(function (): void {
    foreach (['login-ip:127.0.0.1', 'admin-login-ip:127.0.0.1', 'customer-login-ip:127.0.0.1'] as $key) {
        RateLimiter::clear($key);
    }
});

describe('global per-IP login throttling', function () {
    it('blocks the sixth admin attempt even when every attempt uses a different identity', function () {
        RateLimiter::clear('admin-login-ip:127.0.0.1');

        foreach (range(1, 5) as $attempt) {
            Livewire::test(AdminLogin::class)
                ->set('email', "missing-admin-{$attempt}@example.test")
                ->set('password', 'invalid-password')
                ->call('authenticate')
                ->assertHasErrors(['email' => 'Invalid credentials']);
        }

        Livewire::test(AdminLogin::class)
            ->set('email', 'another-admin@example.test')
            ->set('password', 'invalid-password')
            ->call('authenticate')
            ->assertHasErrors(['email' => function (array $rules, array $messages): bool {
                return str_starts_with($messages[0] ?? '', 'Too many attempts. Try again in ');
            }]);
    });

    it('blocks the sixth customer attempt even when every attempt uses a different identity', function () {
        $context = createStoreContext();
        bindStore($context['store']);
        RateLimiter::clear('customer-login-ip:127.0.0.1');

        foreach (range(1, 5) as $attempt) {
            Livewire::test(CustomerLogin::class)
                ->set('email', "missing-customer-{$attempt}@example.test")
                ->set('password', 'invalid-password')
                ->call('login')
                ->assertHasErrors(['credentials' => 'Invalid credentials']);
        }

        Livewire::test(CustomerLogin::class)
            ->set('email', 'another-customer@example.test')
            ->set('password', 'invalid-password')
            ->call('login')
            ->assertHasErrors(['email' => function (array $rules, array $messages): bool {
                return str_starts_with($messages[0] ?? '', 'Too many attempts. Try again in ');
            }])
            ->assertHasNoErrors('credentials');
    });

    it('shares one IP budget across the admin and customer login surfaces', function () {
        $context = createStoreContext();
        bindStore($context['store']);
        foreach (['login-ip:127.0.0.1', 'admin-login-ip:127.0.0.1', 'customer-login-ip:127.0.0.1'] as $key) {
            RateLimiter::clear($key);
        }

        foreach (range(1, 3) as $attempt) {
            Livewire::test(AdminLogin::class)
                ->set('email', "shared-admin-{$attempt}@example.test")
                ->set('password', 'invalid-password')
                ->call('authenticate')
                ->assertHasErrors(['email' => 'Invalid credentials']);
        }
        foreach (range(1, 2) as $attempt) {
            Livewire::test(CustomerLogin::class)
                ->set('email', "shared-customer-{$attempt}@example.test")
                ->set('password', 'invalid-password')
                ->call('login')
                ->assertHasErrors(['credentials' => 'Invalid credentials']);
        }

        Livewire::test(CustomerLogin::class)
            ->set('email', 'shared-customer-final@example.test')
            ->set('password', 'invalid-password')
            ->call('login')
            ->assertHasErrors(['email' => function (array $rules, array $messages): bool {
                return str_starts_with($messages[0] ?? '', 'Too many attempts. Try again in ');
            }])
            ->assertHasNoErrors('credentials');
    });
});

describe('explicit API policy and FormRequest authorization', function () {
    it('requires every admin resource controller action to invoke authorization explicitly', function () {
        $actions = [
            ProductController::class => ['index', 'store', 'show', 'update', 'destroy'],
            CollectionController::class => ['index', 'store', 'update', 'destroy'],
            DiscountController::class => ['index', 'store', 'update', 'destroy'],
            OrderController::class => ['index', 'show', 'fulfill', 'refund', 'confirmPayment'],
            SettingsController::class => ['zones', 'storeZone', 'updateZone', 'storeRate', 'tax', 'updateTax'],
            ContentController::class => ['pages', 'storePage', 'updatePage', 'destroyPage', 'storeTheme', 'publishTheme', 'updateThemeSettings', 'reindex', 'searchStatus'],
            MediaController::class => ['presign'],
            AnalyticsController::class => ['summary', 'exportOrders', 'export'],
        ];
        $missing = [];

        foreach ($actions as $controller => $methods) {
            $lines = file((new ReflectionClass($controller))->getFileName());
            foreach ($methods as $method) {
                $reflection = new ReflectionMethod($controller, $method);
                $source = implode('', array_slice($lines, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
                if (! str_contains($source, 'authorize(')) {
                    $missing[] = class_basename($controller).'::'.$method;
                }
            }
        }

        expect($missing)->toBe([]);
    });

    it('allows privileged create requests and rejects the same requests for support users', function () {
        $owner = createStoreContext('owner');
        bindStore($owner['store']);
        $requestClasses = [
            StoreProductRequest::class,
            StoreCollectionRequest::class,
            StoreDiscountRequest::class,
            StorePageRequest::class,
            StoreShippingRateRequest::class,
            StoreShippingZoneRequest::class,
            UpdateStoreSettingsRequest::class,
            UpdateTaxSettingsRequest::class,
            InviteStaffRequest::class,
        ];

        foreach ($requestClasses as $requestClass) {
            expect(securityFormRequest($requestClass, $owner['user'])->authorize())
                ->toBeTrue("{$requestClass} should authorize the owner role");
        }

        $support = createStoreContext('support');
        bindStore($support['store']);
        foreach ($requestClasses as $requestClass) {
            expect(securityFormRequest($requestClass, $support['user'])->authorize())
                ->toBeFalse("{$requestClass} must reject the support role");
        }
    });

    it('uses tenant-bound model policies for update fulfillment and refund requests', function () {
        $owner = createStoreContext('owner');
        bindStore($owner['store']);
        $product = Product::factory()->for($owner['store'])->create();
        $order = Order::factory()->for($owner['store'])->create();

        $productRoute = securityRoute('/products/{productId}', 'productId', $product->id);
        $orderRoute = securityRoute('/orders/{orderId}', 'orderId', $order->id);
        expect(securityFormRequest(UpdateProductRequest::class, $owner['user'], $productRoute)->authorize())->toBeTrue()
            ->and(securityFormRequest(CreateFulfillmentRequest::class, $owner['user'], $orderRoute)->authorize())->toBeTrue()
            ->and(securityFormRequest(CreateRefundRequest::class, $owner['user'], $orderRoute)->authorize())->toBeTrue();

        $support = createStoreContext('support');
        bindStore($support['store']);
        $supportProduct = Product::factory()->for($support['store'])->create();
        $supportOrder = Order::factory()->for($support['store'])->create();
        $supportProductRoute = securityRoute('/products/{productId}', 'productId', $supportProduct->id);
        $supportOrderRoute = securityRoute('/orders/{orderId}', 'orderId', $supportOrder->id);
        expect(securityFormRequest(UpdateProductRequest::class, $support['user'], $supportProductRoute)->authorize())->toBeFalse()
            ->and(securityFormRequest(CreateFulfillmentRequest::class, $support['user'], $supportOrderRoute)->authorize())->toBeFalse()
            ->and(securityFormRequest(CreateRefundRequest::class, $support['user'], $supportOrderRoute)->authorize())->toBeFalse();

        bindStore($owner['store']);
        expect(securityFormRequest(UpdateProductRequest::class, $owner['user'], $supportProductRoute)->authorize())->toBeFalse();
    });
});

it('exports populated customer_name and shipping_method CSV columns', function () {
    Storage::fake('local');
    $context = createStoreContext();
    $customer = Customer::factory()->for($context['store'])->create(['name' => 'Ada Buyer']);
    $order = Order::factory()->for($context['store'])->for($customer)->create([
        'order_number' => '#EXPORT-1',
        'email' => 'ada@example.test',
    ]);
    $zone = ShippingZone::factory()->for($context['store'])->create();
    $rate = ShippingRate::factory()->for($zone, 'zone')->create(['name' => 'Express Courier']);
    $cart = Cart::factory()->for($context['store'])->create(['customer_id' => $customer->id]);
    Checkout::factory()->completed()->for($context['store'])->for($cart)->create([
        'customer_id' => $customer->id,
        'shipping_method_id' => $rate->id,
        'totals_json' => ['order_id' => $order->id],
    ]);
    $guestOrder = Order::factory()->for($context['store'])->create([
        'customer_id' => null,
        'order_number' => '#EXPORT-GUEST',
        'email' => 'guest@example.test',
        'shipping_address_json' => [
            'first_name' => 'Grace',
            'last_name' => 'Guest',
            'address1' => '2 Example Street',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'postal_code' => '10115',
        ],
    ]);
    $guestCart = Cart::factory()->for($context['store'])->create(['customer_id' => null]);
    Checkout::factory()->completed()->for($context['store'])->for($guestCart)->create([
        'customer_id' => null,
        'shipping_method_id' => $rate->id,
        'totals_json' => ['order_id' => $guestOrder->id],
    ]);
    $export = OrderExport::withoutGlobalScopes()->create([
        'store_id' => $context['store']->id,
        'user_id' => $context['user']->id,
        'format' => 'csv',
        'filters_json' => [],
        'status' => 'queued',
    ]);

    (new GenerateOrderExport($export))->handle();

    $export->refresh();
    Storage::disk('local')->assertExists($export->storage_key);
    $rows = array_map('str_getcsv', preg_split('/\R/', trim(Storage::disk('local')->get($export->storage_key))));
    $records = collect(array_slice($rows, 1))
        ->map(fn (array $row): array => array_combine($rows[0], $row))
        ->keyBy('order_number');
    $record = $records->get('#EXPORT-1');
    $guestRecord = $records->get('#EXPORT-GUEST');

    expect($record)->toBeArray()
        ->and($record['order_number'])->toBe('#EXPORT-1')
        ->and($record['customer_email'])->toBe('ada@example.test')
        ->and($record['customer_name'])->toBe('Ada Buyer')
        ->and($record['shipping_method'])->toBe('Express Courier')
        ->and($guestRecord)->toBeArray()
        ->and($guestRecord['customer_email'])->toBe('guest@example.test')
        ->and($guestRecord['customer_name'])->toBe('Grace Guest')
        ->and($guestRecord['shipping_method'])->toBe('Express Courier');
});

it('denies support users access to the admin dashboard', function () {
    $support = createStoreContext('support');
    actingAsAdmin($support['user'], $support['store']);

    $this->get('/admin')->assertForbidden();
    Livewire::test(AdminDashboard::class)->assertForbidden();
});
