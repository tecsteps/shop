<?php

use App\Models\AnalyticsDaily;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Refund;
use Database\Seeders\CommerceSeeder;
use Database\Seeders\MediaSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

it('seeds a branded image with renditions for every product', function (): void {
    $this->seed(\Database\Seeders\DemoStoreSeeder::class);
    $this->seed(\Database\Seeders\CatalogSeeder::class);

    $this->seed(MediaSeeder::class);

    $productCount = Product::withoutGlobalScopes()->count();

    expect($productCount)->toBeGreaterThan(0);
    expect(ProductMedia::query()->where('status', 'ready')->count())->toBe($productCount);

    // Each media row's file and its three renditions exist on the public disk.
    $media = ProductMedia::query()->first();
    Storage::disk('public')->assertExists($media->storage_key);

    $base = substr($media->storage_key, 0, -4); // strip ".jpg"
    foreach (['thumbnail', 'medium', 'large'] as $size) {
        Storage::disk('public')->assertExists("{$base}-{$size}.jpg");
    }
});

it('is idempotent — re-running does not duplicate media', function (): void {
    $this->seed(\Database\Seeders\DemoStoreSeeder::class);
    $this->seed(\Database\Seeders\CatalogSeeder::class);

    $this->seed(MediaSeeder::class);
    $first = ProductMedia::query()->count();

    $this->seed(MediaSeeder::class);

    expect(ProductMedia::query()->count())->toBe($first);
});

it('seeds orders across every financial and fulfillment status', function (): void {
    $this->seed(\Database\Seeders\DemoStoreSeeder::class);
    $this->seed(\Database\Seeders\CatalogSeeder::class);
    $this->seed(CommerceSeeder::class);

    $orders = Order::withoutGlobalScopes()->get();

    expect($orders->count())->toBeGreaterThanOrEqual(8);

    // Every status the admin Orders tabs filter on is represented.
    $financialStatuses = $orders->pluck('financial_status')->map->value->unique();
    expect($financialStatuses)->toContain('pending', 'paid', 'partially_refunded', 'refunded');

    $fulfillmentStatuses = $orders->pluck('fulfillment_status')->map->value->unique();
    expect($fulfillmentStatuses)->toContain('unfulfilled', 'partial', 'fulfilled');

    // A bank-transfer order still pending payment confirmation exists.
    expect($orders->first(fn (Order $o): bool => $o->payment_method->value === 'bank_transfer' && $o->financial_status->value === 'pending'))->not->toBeNull();

    // A cancelled order exists.
    expect($orders->first(fn (Order $o): bool => $o->status->value === 'cancelled'))->not->toBeNull();
});

it('seeds named customers with addresses and order history', function (): void {
    $this->seed(\Database\Seeders\DemoStoreSeeder::class);
    $this->seed(\Database\Seeders\CatalogSeeder::class);
    $this->seed(CommerceSeeder::class);

    $customers = Customer::withoutGlobalScopes()->get();

    // Demo customer + at least three named customers.
    expect($customers->count())->toBeGreaterThanOrEqual(4);
    expect($customers->firstWhere('email', 'jane.smith@example.com'))->not->toBeNull();

    // Each seeded customer has a default address.
    $jane = $customers->firstWhere('email', 'jane.smith@example.com');
    expect($jane->addresses()->where('is_default', true)->count())->toBe(1);

    // Orders are attached to customers (not all guest).
    expect(Order::withoutGlobalScopes()->whereNotNull('customer_id')->count())->toBeGreaterThan(0);
});

it('records refunds for refunded demo orders', function (): void {
    $this->seed(\Database\Seeders\DemoStoreSeeder::class);
    $this->seed(\Database\Seeders\CatalogSeeder::class);
    $this->seed(CommerceSeeder::class);

    expect(Refund::query()->count())->toBeGreaterThanOrEqual(2);
});

it('seeds ~30 days of daily analytics that the Analytics service reads', function (): void {
    $this->seed(\Database\Seeders\DemoStoreSeeder::class);
    $this->seed(\Database\Seeders\CatalogSeeder::class);
    $this->seed(CommerceSeeder::class);

    $store = app('current_store');
    $rows = AnalyticsDaily::query()->where('store_id', $store->id)->get();

    expect($rows->count())->toBe(30);

    // Funnel must narrow: visits >= add_to_cart >= checkout_started >= checkout_completed,
    // and there must be non-trivial traffic + revenue for the showcase.
    $rows->each(function (AnalyticsDaily $row): void {
        expect($row->visits_count)->toBeGreaterThanOrEqual($row->add_to_cart_count);
        expect($row->add_to_cart_count)->toBeGreaterThanOrEqual($row->checkout_started_count);
        expect($row->checkout_started_count)->toBeGreaterThanOrEqual($row->checkout_completed_count);
    });

    expect($rows->sum('visits_count'))->toBeGreaterThan(0);
    expect($rows->sum('revenue_amount'))->toBeGreaterThan(0);

    // The platform AnalyticsService reads these rows for the admin Analytics page.
    $summary = app(\App\Services\AnalyticsService::class)->summarize(
        $store,
        \Illuminate\Support\Carbon::today()->subDays(29)->toDateString(),
        \Illuminate\Support\Carbon::today()->toDateString(),
    );

    expect($summary['visits_count'])->toBe((int) $rows->sum('visits_count'));
    expect($summary['orders_count'])->toBe((int) $rows->sum('orders_count'));
});
