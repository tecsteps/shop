<?php

use App\Auth\CustomerPasswordBroker;
use App\Livewire\Storefront\Account\Addresses\Index as AddressBook;
use App\Livewire\Storefront\Account\Auth\Login;
use App\Livewire\Storefront\Account\Auth\Register;
use App\Livewire\Storefront\Account\Orders\Show as CustomerOrderShow;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Services\CustomerService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

describe('customer registration and tenant authentication', function () {
    it('registers a normalized hashed per-store customer', function () {
        $store = createStoreContext()['store'];

        $customer = app(CustomerService::class)->register($store, [
            'name' => 'Ada Buyer',
            'email' => 'ADA@EXAMPLE.TEST',
            'password' => 'correct-horse',
            'marketing_opt_in' => true,
        ]);

        expect($customer->email)->toBe('ada@example.test')
            ->and($customer->marketing_opt_in)->toBeTrue()
            ->and(Hash::check('correct-horse', $customer->password_hash))->toBeTrue()
            ->and($customer->password_hash)->not->toBe('correct-horse');
    });

    it('rejects a duplicate account but permits the same email in another store', function () {
        $storeA = createStoreContext()['store'];
        $storeB = createStoreContext()['store'];
        $service = app(CustomerService::class);
        $service->register($storeA, ['name' => 'A', 'email' => 'buyer@example.test', 'password' => 'password-one']);
        $service->register($storeB, ['name' => 'B', 'email' => 'buyer@example.test', 'password' => 'password-two']);

        expect(Customer::withoutGlobalScopes()->where('email', 'buyer@example.test')->count())->toBe(2);

        $service->register($storeA, ['name' => 'Duplicate', 'email' => 'buyer@example.test', 'password' => 'password-three']);
    })->throws(ValidationException::class);

    it('authenticates only against the bound store', function () {
        $storeA = createStoreContext()['store'];
        $storeB = createStoreContext()['store'];
        $customer = Customer::factory()->for($storeA)->create([
            'email' => 'scoped@example.test',
            'password_hash' => Hash::make('right-password'),
        ]);

        bindStore($storeB);
        expect(Auth::guard('customer')->attempt(['email' => $customer->email, 'password' => 'right-password']))->toBeFalse();

        bindStore($storeA);
        expect(Auth::guard('customer')->attempt(['email' => $customer->email, 'password' => 'right-password']))->toBeTrue()
            ->and(Auth::guard('customer')->id())->toBe($customer->id);
    });

    it('renders tenant auth pages and validates registration input', function () {
        $context = createStoreContext();

        $this->get("http://{$context['domain']->hostname}/account/login")->assertOk();
        $this->get("http://{$context['domain']->hostname}/account/register")->assertOk();

        Livewire::test(Register::class)
            ->set('name', 'Livewire Buyer')
            ->set('email', 'not-an-email')
            ->set('password', 'secret-pass')
            ->set('passwordConfirmation', 'different-pass')
            ->call('register')
            ->assertHasErrors(['email', 'password']);
    });

    it('returns one generic credential error and applies the five-attempt limit', function () {
        createStoreContext();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            Livewire::test(Login::class)
                ->set('email', 'unknown@example.test')
                ->set('password', 'wrong-password')
                ->call('login')
                ->assertHasErrors(['credentials']);
        }

        Livewire::test(Login::class)
            ->set('email', 'unknown@example.test')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email'])
            ->assertHasNoErrors(['credentials']);
    });
});

describe('tenant customer password reset', function () {
    it('sends a reset token and resets only the matching store customer', function () {
        Notification::fake();
        $storeA = createStoreContext()['store'];
        $storeB = createStoreContext()['store'];
        $customerA = Customer::factory()->for($storeA)->create(['email' => 'same@example.test']);
        $customerB = Customer::factory()->for($storeB)->create(['email' => 'same@example.test']);
        bindStore($storeA);
        $broker = app(CustomerPasswordBroker::class);

        expect($broker->sendResetLink(['email' => $customerA->email, 'store_id' => $storeA->id]))->toBe(Password::RESET_LINK_SENT);

        $token = null;
        Notification::assertSentTo($customerA, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });
        Notification::assertNotSentTo($customerB, ResetPassword::class);
        expect($token)->toBeString()->not->toBeEmpty();

        $status = $broker->reset([
            'email' => $customerA->email,
            'store_id' => $storeA->id,
            'password' => 'changed-password',
            'password_confirmation' => 'changed-password',
            'token' => $token,
        ], function (Customer $customer): void {
            $customer->forceFill(['password_hash' => Hash::make('changed-password')])->save();
        });

        expect($status)->toBe(Password::PASSWORD_RESET)
            ->and(Hash::check('changed-password', $customerA->refresh()->password_hash))->toBeTrue()
            ->and(Hash::check('changed-password', $customerB->refresh()->password_hash))->toBeFalse();
    });

    it('does not accept a reset token in another store', function () {
        Notification::fake();
        $storeA = createStoreContext()['store'];
        $storeB = createStoreContext()['store'];
        $customerA = Customer::factory()->for($storeA)->create(['email' => 'shared@example.test']);
        Customer::factory()->for($storeB)->create(['email' => 'shared@example.test']);
        bindStore($storeA);
        $broker = app(CustomerPasswordBroker::class);
        $broker->sendResetLink(['email' => $customerA->email, 'store_id' => $storeA->id]);
        $token = null;
        Notification::assertSentTo($customerA, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        bindStore($storeB);
        $status = $broker->reset([
            'email' => 'shared@example.test',
            'store_id' => $storeB->id,
            'password' => 'changed-password',
            'password_confirmation' => 'changed-password',
            'token' => $token,
        ], fn () => null);

        expect($status)->toBe(Password::INVALID_TOKEN);
    });
});

describe('customer-owned data', function () {
    it('creates updates defaults and deletes only owned addresses', function () {
        $store = createStoreContext()['store'];
        $customer = Customer::factory()->for($store)->create();
        actingAsCustomer($customer);
        $component = Livewire::test(AddressBook::class);

        $component->call('createAddress')
            ->set('label', 'Home')
            ->set('address.first_name', 'Ada')
            ->set('address.last_name', 'Lovelace')
            ->set('address.address1', 'Home Street 1')
            ->set('address.city', 'Berlin')
            ->set('address.postal_code', '10115')
            ->set('address.country', 'DE')
            ->call('saveAddress')
            ->assertHasNoErrors();

        $home = $customer->addresses()->firstOrFail();
        expect($home->is_default)->toBeTrue();

        $component->call('createAddress')
            ->set('label', 'Work')
            ->set('address.first_name', 'Ada')
            ->set('address.last_name', 'Lovelace')
            ->set('address.address1', 'Work Street 2')
            ->set('address.city', 'Berlin')
            ->set('address.postal_code', '10117')
            ->set('address.country', 'DE')
            ->call('saveAddress');
        $work = $customer->addresses()->where('label', 'Work')->firstOrFail();

        $component->call('setDefault', $work->id);
        expect($work->refresh()->is_default)->toBeTrue()
            ->and($home->refresh()->is_default)->toBeFalse();

        $component->call('editAddress', $work->id)
            ->set('address.city', 'Potsdam')
            ->call('saveAddress');
        expect($work->refresh()->address_json['city'])->toBe('Potsdam');

        $component->call('deleteAddress', $work->id);
        expect(CustomerAddress::query()->find($work->id))->toBeNull()
            ->and($home->refresh()->is_default)->toBeTrue();
    });

    it('rejects another customers address and order identifiers', function () {
        $store = createStoreContext()['store'];
        $customerA = Customer::factory()->for($store)->create();
        $customerB = Customer::factory()->for($store)->create();
        $foreignAddress = CustomerAddress::factory()->for($customerB)->create();
        $foreignOrder = Order::factory()->for($store)->for($customerB)->create(['order_number' => '#FOREIGN']);
        actingAsCustomer($customerA);

        expect(fn () => Livewire::test(AddressBook::class)->call('editAddress', $foreignAddress->id))
            ->toThrow(ModelNotFoundException::class)
            ->and(fn () => Livewire::test(CustomerOrderShow::class, ['orderNumber' => $foreignOrder->order_number]))
            ->toThrow(ModelNotFoundException::class);
    });
});
