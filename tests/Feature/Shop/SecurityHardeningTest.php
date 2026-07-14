<?php

use App\Livewire\Admin\Settings\Index as AdminSettings;
use App\Livewire\Storefront\Checkout\Confirmation;
use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Fulfillment;
use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Theme;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

describe('admin identity hardening', function () {
    it('rejects disabled users through Fortify and expires an existing web session', function () {
        $user = User::factory()->disabled()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest('web');

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest('web');
    });

    it('rejects and revokes a disabled users Sanctum token', function () {
        $context = createStoreContext();
        $token = $context['user']->createToken('disabled-user', [
            'store:'.$context['store']->id,
        ]);
        $context['user']->update(['status' => 'disabled']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/admin/v1/stores/'.$context['store']->id.'/me')
            ->assertUnauthorized();

        expect(DB::table('personal_access_tokens')->where('id', $token->accessToken->id)->exists())->toBeFalse();
    });

    it('builds reset links from the canonical application URL regardless of Host', function () {
        Notification::fake();
        config(['app.url' => 'https://canonical.shop.test']);
        request()->headers->set('host', 'attacker.example');
        $user = User::factory()->create();

        expect(Password::broker('users')->sendResetLink(['email' => $user->email]))
            ->toBe(Password::RESET_LINK_SENT);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, 'https://canonical.shop.test/admin/reset-password/')
                && ! str_contains($url, 'attacker.example');
        });
    });
});

describe('customer identity hardening', function () {
    it('creates a fresh account without claiming a guest customer or historical orders', function () {
        $store = createStoreContext()['store'];
        $guest = Customer::factory()->guest()->for($store)->create(['email' => 'buyer@example.test']);
        $historical = Order::factory()->for($store)->for($guest)->create(['email' => 'buyer@example.test']);

        $account = app(CustomerService::class)->register($store, [
            'name' => 'Buyer',
            'email' => 'buyer@example.test',
            'password' => 'secure-password',
        ]);

        expect($account->id)->not->toBe($guest->id)
            ->and($account->email)->toBe('buyer@example.test')
            ->and($guest->refresh()->email)->toEndWith('@unclaimed.invalid')
            ->and($historical->refresh()->customer_id)->toBe($guest->id)
            ->and($account->orders()->count())->toBe(0);
    });

    it('does not restore a customer session or remember token in another store', function () {
        $storeA = createStoreContext()['store'];
        $storeB = createStoreContext()['store'];
        $customer = Customer::factory()->for($storeA)->create();
        bindStore($storeB);

        $provider = Auth::createUserProvider('customers');

        expect($provider?->retrieveById($customer->id))->toBeNull()
            ->and($provider?->retrieveByToken($customer->id, 'remember-me'))->toBeNull();
    });
});

describe('untrusted persisted content hardening', function () {
    it('permits relative and HTTP links but drops executable URL schemes', function () {
        $store = createStoreContext()['store'];
        $menu = NavigationMenu::factory()->for($store)->create();
        $unsafeItem = NavigationItem::factory()->for($menu, 'menu')->create(['url' => 'javascript:alert(1)']);
        $safeItem = NavigationItem::factory()->for($menu, 'menu')->create(['url' => '/collections/new']);
        $theme = Theme::factory()->for($store)->create();
        $settings = $theme->settings()->create(['settings_json' => [
            'announcement' => ['url' => 'javascript:alert(1)'],
            'home' => ['hero' => ['cta_url' => 'https://example.test/sale']],
        ]]);
        $order = Order::factory()->for($store)->create(['customer_id' => null]);
        $fulfillment = Fulfillment::factory()->for($order)->create(['tracking_url' => 'data:text/html,pwned']);

        expect($unsafeItem->url)->toBeNull()
            ->and(DB::table('navigation_items')->where('id', $unsafeItem->id)->value('url'))->toBeNull()
            ->and($safeItem->url)->toBe('/collections/new')
            ->and(data_get($settings->settings_json, 'announcement.url'))->toBeNull()
            ->and(data_get($settings->settings_json, 'home.hero.cta_url'))->toBe('https://example.test/sale')
            ->and($fulfillment->tracking_url)->toBeNull()
            ->and(DB::table('fulfillments')->where('id', $fulfillment->id)->value('tracking_url'))->toBeNull();
    });

    it('never serializes encrypted provider payment payloads', function () {
        $payment = Payment::factory()->create(['raw_json_encrypted' => ['secret' => 'provider-data']]);

        expect($payment->toArray())->not->toHaveKey('raw_json_encrypted')
            ->and($payment->toJson())->not->toContain('provider-data');
    });
});

it('resolves checkout confirmation only from the immutable order id snapshot', function () {
    $store = createStoreContext()['store'];
    $cart = Cart::factory()->for($store)->create();
    $expected = Order::factory()->for($store)->create(['customer_id' => null, 'email' => 'guest@example.test']);
    $other = Order::factory()->for($store)->create(['customer_id' => null, 'email' => 'guest@example.test']);
    $checkout = Checkout::factory()->completed()->for($store)->for($cart)->create([
        'email' => 'guest@example.test',
        'totals_json' => ['order_id' => $expected->id],
    ]);
    session([
        'checkout_access.'.$checkout->id => true,
        'last_order_id' => $other->id,
    ]);

    Livewire::test(Confirmation::class, ['checkoutId' => $checkout->id])
        ->assertSet('order.id', $expected->id);
});

it('blocks Livewire mutations after a store is suspended', function () {
    $context = createStoreContext();
    actingAsAdmin($context['user'], $context['store']);
    $context['store']->update(['status' => 'suspended']);
    bindStore($context['store']->refresh());
    app()->instance('request', Request::create('/livewire/update', 'POST'));

    expect(fn () => (new AdminSettings)->boot())
        ->toThrow(HttpException::class, 'This store is suspended.');
});
