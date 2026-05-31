<?php

use App\Enums\ProductStatus;
use App\Enums\StoreUserRole;
use App\Livewire\Admin\Products\Form;
use App\Livewire\Admin\Products\Index;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->owner = $this->context['owner'];
});

/**
 * Attach a user to the current store with the given role and return it.
 */
function staffWithRole(StoreUserRole $role): User
{
    $user = User::factory()->create();
    $store = app('current_store');
    $store->users()->attach($user->id, ['role' => $role->value]);

    return $user;
}

it('lists products with pagination', function (): void {
    actingAsAdmin($this->owner, $this->store);

    Product::factory()->count(25)->create(['store_id' => $this->store->id]);

    $component = Livewire::test(Index::class)->assertOk();

    expect($component->instance()->products->count())->toBe(15);
    expect($component->instance()->products->total())->toBe(25);
});

it('creates a product via admin form', function (): void {
    actingAsAdmin($this->owner, $this->store);

    Livewire::test(Form::class)
        ->set('title', 'Blue Cotton Shirt')
        ->set('descriptionHtml', 'A nice shirt')
        ->set('status', 'active')
        ->set('variants.0.price', '29.99')
        ->set('variants.0.quantity', '10')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('products', [
        'store_id' => $this->store->id,
        'title' => 'Blue Cotton Shirt',
    ]);
});

it('edits a product via admin form', function (): void {
    actingAsAdmin($this->owner, $this->store);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id, 'title' => 'Old Title']);

    Livewire::test(Form::class, ['product' => $product])
        ->set('title', 'New Title')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->title)->toBe('New Title');
});

it('bulk archives selected products', function (): void {
    actingAsAdmin($this->owner, $this->store);

    $products = Product::factory()->active()->count(3)->create(['store_id' => $this->store->id]);
    $products->each(fn ($p) => $p->variants()->create(['price_amount' => 1000, 'currency' => 'USD', 'is_default' => true]));

    Livewire::test(Index::class)
        ->set('selectedIds', $products->pluck('id')->all())
        ->call('bulkArchive');

    foreach ($products as $product) {
        expect($product->fresh()->status)->toBe(ProductStatus::Archived);
    }
});

it('uploads media from the product form', function (): void {
    Storage::fake('public');
    actingAsAdmin($this->owner, $this->store);

    $product = Product::factory()->create(['store_id' => $this->store->id]);

    Livewire::test(App\Livewire\Admin\Products\MediaManager::class, ['product' => $product])
        ->set('upload', UploadedFile::fake()->image('shirt.jpg'))
        ->call('save');

    expect($product->fresh()->media()->count())->toBe(1);
});

it('manages variants from the product form', function (): void {
    actingAsAdmin($this->owner, $this->store);

    $component = Livewire::test(Form::class)
        ->set('title', 'Variant Product')
        ->set('options.0.name', 'Size')
        ->set('options.0.values', 'S, M, L')
        ->call('generateVariants');

    expect($component->get('variants'))->toHaveCount(3);

    $component->set('variants.0.price', '10')
        ->set('variants.1.price', '10')
        ->set('variants.2.price', '10')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('title', 'Variant Product')->first();
    expect($product->variants()->count())->toBe(3);
});

it('restricts product management to authorized roles', function (): void {
    $support = staffWithRole(StoreUserRole::Support);
    actingAsAdmin($support, $this->store);

    // Support can view the list (read-only).
    Livewire::test(Index::class)->assertOk();

    // Support cannot create.
    Livewire::test(Form::class)
        ->assertForbidden();
});

it('staff can create but not delete products', function (): void {
    $staff = staffWithRole(StoreUserRole::Staff);
    actingAsAdmin($staff, $this->store);

    // Staff can create.
    Livewire::test(Form::class)
        ->set('title', 'Staff Product')
        ->set('variants.0.price', '5')
        ->set('variants.0.quantity', '0')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);

    // Staff cannot delete/archive.
    Livewire::test(Form::class, ['product' => $product])
        ->call('deleteProduct')
        ->assertForbidden();
});
