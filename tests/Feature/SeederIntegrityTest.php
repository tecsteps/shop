<?php

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('seeds the complete deterministic demo scenario idempotently', function (): void {
    $this->seed(DatabaseSeeder::class);

    $expectedCounts = [
        'organizations' => 1,
        'stores' => 2,
        'store_domains' => 3,
        'users' => 5,
        'store_users' => 5,
        'store_settings' => 2,
        'tax_settings' => 2,
        'shipping_zones' => 4,
        'shipping_rates' => 5,
        'collections' => 6,
        'products' => 25,
        'product_variants' => 127,
        'inventory_items' => 127,
        'product_media' => 0,
        'discounts' => 5,
        'customers' => 12,
        'customer_addresses' => 13,
        'orders' => 18,
        'order_lines' => 26,
        'payments' => 18,
        'fulfillments' => 7,
        'fulfillment_lines' => 11,
        'refunds' => 2,
        'themes' => 2,
        'theme_settings' => 2,
        'pages' => 5,
        'navigation_menus' => 3,
        'navigation_items' => 13,
        'analytics_daily' => 31,
        'analytics_events' => 220,
        'search_settings' => 2,
    ];

    foreach ($expectedCounts as $table => $count) {
        $this->assertDatabaseCount($table, $count);
    }

    $fashion = Store::query()->where('handle', 'acme-fashion')->sole();
    $electronics = Store::query()->where('handle', 'acme-electronics')->sole();
    expect(Product::withoutGlobalScopes()->where('store_id', $fashion->id)->count())->toBe(20)
        ->and(Product::withoutGlobalScopes()->where('store_id', $electronics->id)->count())->toBe(5)
        ->and(Product::withoutGlobalScopes()->where('store_id', $fashion->id)->whereHas('variants')->withCount('variants')->get()->sum('variants_count'))->toBe(117)
        ->and(Product::withoutGlobalScopes()->where('store_id', $electronics->id)->withCount('variants')->get()->sum('variants_count'))->toBe(10);

    $admin = User::query()->where('email', 'admin@acme.test')->sole();
    $customer = Customer::withoutGlobalScopes()->where('store_id', $fashion->id)->where('email', 'customer@acme.test')->sole();
    expect(Hash::check('password', $admin->password_hash))->toBeTrue()
        ->and(Hash::check('password', $customer->password_hash))->toBeTrue()
        ->and($customer->addresses()->count())->toBe(2);

    $soldOut = Product::withoutGlobalScopes()->where('store_id', $fashion->id)->where('handle', 'limited-edition-sneakers')->sole();
    $backorder = Product::withoutGlobalScopes()->where('store_id', $fashion->id)->where('handle', 'backorder-denim-jacket')->sole();
    $giftCard = Product::withoutGlobalScopes()->where('store_id', $fashion->id)->where('handle', 'gift-card')->sole();
    expect($soldOut->variants()->whereHas('inventoryItem', fn ($query) => $query->where('quantity_on_hand', 0)->where('policy', 'deny'))->count())->toBe(3)
        ->and($backorder->variants()->whereHas('inventoryItem', fn ($query) => $query->where('quantity_on_hand', 0)->where('policy', 'continue'))->count())->toBe(4)
        ->and($giftCard->variants()->where('requires_shipping', false)->count())->toBe(3);

    expect(Discount::withoutGlobalScopes()->where('store_id', $fashion->id)->where('code', 'MAXED')->value('usage_count'))->toBe(5)
        ->and(Discount::withoutGlobalScopes()->where('store_id', $fashion->id)->where('code', 'EXPIRED20')->value('status')->value)->toBe('expired')
        ->and(Order::withoutGlobalScopes()->where('store_id', $fashion->id)->where('order_number', '#1015')->value('discount_amount'))->toBe(550)
        ->and(Order::withoutGlobalScopes()->where('store_id', $electronics->id)->pluck('order_number')->all())->toBe(['#5001', '#5002', '#5003']);

    $pendingBankTransfer = Order::withoutGlobalScopes()
        ->with('lines.variant.inventoryItem')
        ->where('store_id', $fashion->id)
        ->where('order_number', '#1005')
        ->sole();
    expect($pendingBankTransfer->lines->every(
        fn ($line): bool => $line->variant->inventoryItem->quantity_reserved >= $line->quantity,
    ))->toBeTrue();

    $countsBeforeSecondRun = collect($expectedCounts)->mapWithKeys(fn (int $count, string $table): array => [$table => DB::table($table)->count()]);
    $this->seed(DatabaseSeeder::class);
    $countsAfterSecondRun = collect($expectedCounts)->mapWithKeys(fn (int $count, string $table): array => [$table => DB::table($table)->count()]);

    expect($countsAfterSecondRun->all())->toBe($countsBeforeSecondRun->all());
});
