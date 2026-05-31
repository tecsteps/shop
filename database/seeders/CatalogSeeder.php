<?php

namespace Database\Seeders;

use App\Enums\CollectionStatus;
use App\Enums\InventoryPolicy;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds a realistic catalog for the demo store: a spread of products with
 * options/variants/inventory plus a few collections grouping them.
 *
 * Binds the demo store as `current_store` first so store-scoped models resolve
 * correctly, then builds products through {@see ProductService} (the same path
 * the admin uses). Re-running is safe: products are keyed by handle and skipped
 * if already present.
 */
class CatalogSeeder extends Seeder
{
    public function __construct(
        private readonly ProductService $products,
    ) {}

    /**
     * Demo product blueprints. A product either lists option dimensions (a
     * variant matrix is built) or none (a single default variant).
     *
     * @var list<array{
     *     title: string,
     *     vendor: string,
     *     type: string,
     *     price: int,
     *     tags: list<string>,
     *     options?: list<array{name: string, values: list<string>}>,
     *     collections: list<string>,
     * }>
     */
    private const PRODUCTS = [
        ['title' => 'Classic Cotton T-Shirt', 'vendor' => 'Acme Basics', 'type' => 'Apparel', 'price' => 2500, 'tags' => ['summer', 'basics'], 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']], ['name' => 'Color', 'values' => ['White', 'Black', 'Navy']]], 'collections' => ['summer-essentials', 'best-sellers']],
        ['title' => 'Organic Cotton Hoodie', 'vendor' => 'Acme Basics', 'type' => 'Apparel', 'price' => 6500, 'tags' => ['cozy', 'organic'], 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L']], ['name' => 'Color', 'values' => ['Heather Grey', 'Forest']]], 'collections' => ['best-sellers']],
        ['title' => 'Slim Fit Chinos', 'vendor' => 'Northbound', 'type' => 'Apparel', 'price' => 7900, 'tags' => ['workwear'], 'options' => [['name' => 'Waist', 'values' => ['30', '32', '34', '36']]], 'collections' => []],
        ['title' => 'Merino Wool Beanie', 'vendor' => 'Northbound', 'type' => 'Accessories', 'price' => 3200, 'tags' => ['winter'], 'options' => [['name' => 'Color', 'values' => ['Charcoal', 'Burgundy', 'Camel']]], 'collections' => ['winter-warmers']],
        ['title' => 'Canvas Tote Bag', 'vendor' => 'Acme Basics', 'type' => 'Accessories', 'price' => 1800, 'tags' => ['everyday'], 'collections' => ['best-sellers']],
        ['title' => 'Leather Card Holder', 'vendor' => 'Atelier', 'type' => 'Accessories', 'price' => 4500, 'tags' => ['gift'], 'options' => [['name' => 'Color', 'values' => ['Tan', 'Black']]], 'collections' => []],
        ['title' => 'Running Sneakers', 'vendor' => 'Velocity', 'type' => 'Footwear', 'price' => 11900, 'tags' => ['sport', 'featured'], 'options' => [['name' => 'Size', 'values' => ['8', '9', '10', '11', '12']]], 'collections' => ['best-sellers']],
        ['title' => 'Suede Chelsea Boots', 'vendor' => 'Atelier', 'type' => 'Footwear', 'price' => 15900, 'tags' => ['winter'], 'options' => [['name' => 'Size', 'values' => ['8', '9', '10', '11']]], 'collections' => ['winter-warmers']],
        ['title' => 'Linen Button-Down Shirt', 'vendor' => 'Northbound', 'type' => 'Apparel', 'price' => 6900, 'tags' => ['summer'], 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']], ['name' => 'Color', 'values' => ['White', 'Sky']]], 'collections' => ['summer-essentials']],
        ['title' => 'Stainless Water Bottle', 'vendor' => 'Acme Basics', 'type' => 'Home', 'price' => 2900, 'tags' => ['everyday'], 'options' => [['name' => 'Size', 'values' => ['500ml', '750ml']]], 'collections' => []],
        ['title' => 'Ceramic Coffee Mug', 'vendor' => 'Hearth', 'type' => 'Home', 'price' => 1500, 'tags' => ['home'], 'options' => [['name' => 'Color', 'values' => ['Sand', 'Slate', 'Sage']]], 'collections' => []],
        ['title' => 'Wool Throw Blanket', 'vendor' => 'Hearth', 'type' => 'Home', 'price' => 8900, 'tags' => ['cozy', 'winter'], 'collections' => ['winter-warmers']],
        ['title' => 'Polarized Sunglasses', 'vendor' => 'Velocity', 'type' => 'Accessories', 'price' => 5900, 'tags' => ['summer', 'featured'], 'options' => [['name' => 'Color', 'values' => ['Tortoise', 'Matte Black']]], 'collections' => ['summer-essentials']],
        ['title' => 'Performance Socks 3-Pack', 'vendor' => 'Velocity', 'type' => 'Apparel', 'price' => 1900, 'tags' => ['sport'], 'options' => [['name' => 'Size', 'values' => ['M', 'L']]], 'collections' => []],
        ['title' => 'Quilted Field Jacket', 'vendor' => 'Northbound', 'type' => 'Apparel', 'price' => 18900, 'tags' => ['winter', 'featured'], 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']]], 'collections' => ['winter-warmers', 'best-sellers']],
        ['title' => 'Bamboo Cutting Board', 'vendor' => 'Hearth', 'type' => 'Home', 'price' => 3400, 'tags' => ['home'], 'collections' => []],
        ['title' => 'Travel Backpack 25L', 'vendor' => 'Velocity', 'type' => 'Accessories', 'price' => 12900, 'tags' => ['travel', 'featured'], 'options' => [['name' => 'Color', 'values' => ['Olive', 'Black']]], 'collections' => ['best-sellers']],
        ['title' => 'Cashmere Scarf', 'vendor' => 'Atelier', 'type' => 'Accessories', 'price' => 9900, 'tags' => ['winter', 'gift'], 'options' => [['name' => 'Color', 'values' => ['Camel', 'Grey', 'Plum']]], 'collections' => ['winter-warmers']],
        ['title' => 'Denim Jacket', 'vendor' => 'Northbound', 'type' => 'Apparel', 'price' => 9900, 'tags' => ['everyday'], 'options' => [['name' => 'Size', 'values' => ['S', 'M', 'L', 'XL']]], 'collections' => ['best-sellers']],
        ['title' => 'Yoga Mat', 'vendor' => 'Velocity', 'type' => 'Home', 'price' => 4900, 'tags' => ['sport'], 'options' => [['name' => 'Color', 'values' => ['Coral', 'Teal', 'Charcoal']]], 'collections' => []],
    ];

    /**
     * Collections to provision, keyed by handle.
     *
     * @var array<string, string>
     */
    private const COLLECTIONS = [
        'summer-essentials' => 'Summer Essentials',
        'winter-warmers' => 'Winter Warmers',
        'best-sellers' => 'Best Sellers',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $store = (new DemoStoreSeeder)->store();

        // Bind the demo store so store-scoped models resolve to it.
        app()->instance('current_store', $store);

        $collections = $this->seedCollections($store);

        foreach (self::PRODUCTS as $blueprint) {
            $this->seedProduct($store, $blueprint, $collections);
        }
    }

    /**
     * Create (idempotently) the demo collections.
     *
     * @return array<string, Collection> Keyed by handle.
     */
    private function seedCollections(Store $store): array
    {
        $result = [];

        foreach (self::COLLECTIONS as $handle => $title) {
            $result[$handle] = Collection::firstOrCreate(
                ['store_id' => $store->id, 'handle' => $handle],
                ['title' => $title, 'status' => CollectionStatus::Active->value],
            );
        }

        return $result;
    }

    /**
     * Create a single demo product with its variants, inventory, and collection
     * memberships. Skips creation when a product with the same handle exists.
     *
     * @param  array{title: string, vendor: string, type: string, price: int, tags: list<string>, options?: list<array{name: string, values: list<string>}>, collections: list<string>}  $blueprint
     * @param  array<string, Collection>  $collections
     */
    private function seedProduct(Store $store, array $blueprint, array $collections): void
    {
        // Skip on re-run: match the base slug so a previously seeded product
        // (whose stored handle may carry a collision suffix) is recognised.
        $baseHandle = Str::slug($blueprint['title']);

        if (Product::where('handle', 'like', $baseHandle.'%')->exists()) {
            return;
        }

        $product = $this->products->create($store, [
            'title' => $blueprint['title'],
            'status' => 'active',
            'vendor' => $blueprint['vendor'],
            'product_type' => $blueprint['type'],
            'tags' => $blueprint['tags'],
            'options' => $blueprint['options'] ?? [],
        ]);

        $this->priceAndStockVariants($product, $blueprint['price']);

        $this->attachToCollections($product, $blueprint['collections'], $collections);
    }

    /**
     * Set a base price on every variant and seed inventory. Variants get a
     * small price spread so the catalog looks realistic.
     */
    private function priceAndStockVariants(Product $product, int $basePrice): void
    {
        foreach ($product->variants()->get() as $offset => $variant) {
            $variant->update([
                'price_amount' => $basePrice + ($offset * 100),
                'compare_at_amount' => $basePrice + 1500,
            ]);

            $variant->inventoryItem()->update([
                'quantity_on_hand' => fake()->numberBetween(0, 80),
                'policy' => fake()->boolean(80) ? InventoryPolicy::Deny->value : InventoryPolicy::Continue->value,
            ]);
        }
    }

    /**
     * Attach a product to its collections by handle.
     *
     * @param  list<string>  $handles
     * @param  array<string, Collection>  $collections
     */
    private function attachToCollections(Product $product, array $handles, array $collections): void
    {
        foreach ($handles as $handle) {
            if (! isset($collections[$handle])) {
                continue;
            }

            $collection = $collections[$handle];
            $position = (int) $collection->products()->count();

            $collection->products()->syncWithoutDetaching([
                $product->id => ['position' => $position],
            ]);
        }
    }
}
