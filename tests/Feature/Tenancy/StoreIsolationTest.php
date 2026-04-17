<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    storeIsolationEnsureSchema();
    app()->forgetInstance('current_store');
});

function storeIsolationEnsureSchema(): void
{
    if (! Schema::hasTable('organizations')) {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('billing_email');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('stores')) {
        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('handle');
            $table->string('status')->default('active');
            $table->string('default_currency', 3)->default('USD');
            $table->string('default_locale', 10)->default('en');
            $table->string('timezone')->default('UTC');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('scoped_products')) {
        Schema::create('scoped_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('scoped_orders')) {
        Schema::create('scoped_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('number');
            $table->timestamps();
        });
    }
}

function storeIsolationSeedStore(string $status = 'active'): int
{
    $now = now();
    $suffix = Str::lower(Str::random(6));

    $organizationId = DB::table('organizations')->insertGetId([
        'name' => 'Org '.$suffix,
        'billing_email' => "billing-{$suffix}@example.test",
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return DB::table('stores')->insertGetId([
        'organization_id' => $organizationId,
        'name' => 'Store '.$suffix,
        'handle' => 'store-'.$suffix,
        'status' => $status,
        'default_currency' => 'USD',
        'default_locale' => 'en',
        'timezone' => 'UTC',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

function storeIsolationScopedModel(string $table): Model
{
    expect(class_exists(\App\Models\Scopes\StoreScope::class))
        ->toBeTrue('StoreScope is missing.');

    $model = new class extends Model
    {
        protected $guarded = [];

        protected static function booted(): void
        {
            self::addGlobalScope(app(\App\Models\Scopes\StoreScope::class));
        }
    };

    $model->setTable($table);

    return $model;
}

function storeIsolationBelongsToStoreModel(string $table): Model
{
    expect(trait_exists(\App\Models\Concerns\BelongsToStore::class))
        ->toBeTrue('BelongsToStore trait is missing.');

    $model = new class extends Model
    {
        use \App\Models\Concerns\BelongsToStore;

        protected $guarded = [];
    };

    $model->setTable($table);

    return $model;
}

test('scopes product-like queries to the current store', function (): void {
    $storeAId = storeIsolationSeedStore();
    $storeBId = storeIsolationSeedStore();

    $now = now();

    for ($index = 0; $index < 3; $index++) {
        DB::table('scoped_products')->insert([
            'store_id' => $storeAId,
            'name' => "A Product {$index}",
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    for ($index = 0; $index < 5; $index++) {
        DB::table('scoped_products')->insert([
            'store_id' => $storeBId,
            'name' => "B Product {$index}",
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    app()->instance('current_store', (object) ['id' => $storeAId]);

    $productModel = storeIsolationScopedModel('scoped_products');

    expect($productModel->newQuery()->count())->toBe(3);
});

test('scopes order-like queries to the current store', function (): void {
    $storeAId = storeIsolationSeedStore();
    $storeBId = storeIsolationSeedStore();

    $now = now();

    for ($index = 0; $index < 2; $index++) {
        DB::table('scoped_orders')->insert([
            'store_id' => $storeAId,
            'number' => "A-{$index}",
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    for ($index = 0; $index < 7; $index++) {
        DB::table('scoped_orders')->insert([
            'store_id' => $storeBId,
            'number' => "B-{$index}",
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    app()->instance('current_store', (object) ['id' => $storeAId]);

    $orderModel = storeIsolationScopedModel('scoped_orders');

    expect($orderModel->newQuery()->count())->toBe(2);
});

test('automatically sets store_id on model creation', function (): void {
    $storeAId = storeIsolationSeedStore();

    app()->instance('current_store', (object) ['id' => $storeAId]);

    $productModel = storeIsolationBelongsToStoreModel('scoped_products');

    $created = $productModel->newQuery()->create([
        'name' => 'Scoped Product',
    ]);

    expect((int) $created->getAttribute('store_id'))->toBe($storeAId);
});

test('prevents accessing another store records via direct id', function (): void {
    $storeAId = storeIsolationSeedStore();
    $storeBId = storeIsolationSeedStore();

    $productId = DB::table('scoped_products')->insertGetId([
        'store_id' => $storeAId,
        'name' => 'A Product',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app()->instance('current_store', (object) ['id' => $storeBId]);

    $productModel = storeIsolationScopedModel('scoped_products');

    expect($productModel->newQuery()->find($productId))->toBeNull();
});

test('allows cross-store access when store scope is removed', function (): void {
    $storeAId = storeIsolationSeedStore();
    $storeBId = storeIsolationSeedStore();

    $now = now();

    DB::table('scoped_products')->insert([
        [
            'store_id' => $storeAId,
            'name' => 'A Product',
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'store_id' => $storeBId,
            'name' => 'B Product',
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    app()->instance('current_store', (object) ['id' => $storeAId]);

    $productModel = storeIsolationScopedModel('scoped_products');

    expect(
        $productModel
            ->newQuery()
            ->withoutGlobalScope(\App\Models\Scopes\StoreScope::class)
            ->count()
    )->toBe(2);
});
