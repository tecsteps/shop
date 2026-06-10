<?php

use App\Enums\StoreUserRole;
use App\Livewire\Admin\Developers\Index as DevelopersIndex;
use Livewire\Livewire;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('renders the developers page for an owner', function () {
    actingAsAdmin($this->user)
        ->get('/admin/developers')
        ->assertOk()
        ->assertSee('API tokens');
});

it('generates a token and shows the plain text value once', function () {
    actingAsAdmin($this->user);

    Livewire::test(DevelopersIndex::class)
        ->set('newTokenName', 'My integration')
        ->set('newTokenAbilities', ['read-products', 'write-products'])
        ->call('generateToken')
        ->assertHasNoErrors()
        ->assertSet('generatedToken', fn (?string $token): bool => $token !== null && str_contains($token, 'shop_'));

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $this->user->getKey(),
        'name' => 'My integration',
    ]);
});

it('requires at least one ability when generating a token', function () {
    actingAsAdmin($this->user);

    Livewire::test(DevelopersIndex::class)
        ->set('newTokenName', 'No abilities')
        ->set('newTokenAbilities', [])
        ->call('generateToken')
        ->assertHasErrors('newTokenAbilities');
});

it('revokes a token', function () {
    $token = $this->user->createToken('Revocable', ['read-products']);

    actingAsAdmin($this->user);

    Livewire::test(DevelopersIndex::class)
        ->call('revokeToken', $token->accessToken->getKey());

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->getKey(),
    ]);
});

it('restricts the developers page to owner and admin roles', function () {
    $staff = createStoreMember($this->store, StoreUserRole::Staff);

    actingAsAdmin($staff, $this->store)
        ->get('/admin/developers')
        ->assertForbidden();

    $admin = createStoreMember($this->store, StoreUserRole::Admin);

    actingAsAdmin($admin, $this->store)
        ->get('/admin/developers')
        ->assertOk();
});
