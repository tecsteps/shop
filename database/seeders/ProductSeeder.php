<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $electronics = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        $products = [
            ['Classic Cotton T-Shirt', 'classic-cotton-t-shirt', 'Acme Basics', 'T-Shirts', ['new', 'popular'], 2499, null, 200, 15, 'deny', true, ['Size' => ['S', 'M', 'L', 'XL'], 'Color' => ['White', 'Black', 'Navy']], ['new-arrivals', 't-shirts'], 'A timeless classic cotton t-shirt. Comfortable, breathable, and perfect for everyday wear.'],
            ['Premium Slim Fit Jeans', 'premium-slim-fit-jeans', 'Acme Denim', 'Pants', ['new', 'sale'], 7999, 9999, 800, 8, 'deny', true, ['Size' => ['28', '30', '32', '34', '36'], 'Color' => ['Blue', 'Black']], ['new-arrivals', 'pants-jeans', 'sale'], 'Slim fit jeans crafted from premium stretch denim. Comfortable all-day wear with a modern silhouette.'],
            ['Organic Hoodie', 'organic-hoodie', 'Acme Basics', 'Hoodies', ['new', 'trending'], 5999, null, 500, 20, 'deny', true, ['Size' => ['S', 'M', 'L', 'XL']], ['new-arrivals'], 'Made from 100% organic cotton. Warm, soft, and sustainably produced.'],
            ['Leather Belt', 'leather-belt', 'Acme Accessories', 'Accessories', ['popular'], 3499, null, 150, 25, 'deny', true, ['Size' => ['S/M', 'L/XL'], 'Color' => ['Brown', 'Black']], [], 'Genuine leather belt with brushed metal buckle. A wardrobe essential.'],
            ['Running Sneakers', 'running-sneakers', 'Acme Sport', 'Shoes', ['trending'], 11999, null, 600, 5, 'deny', true, ['Size' => ['EU 38', 'EU 39', 'EU 40', 'EU 41', 'EU 42', 'EU 43', 'EU 44'], 'Color' => ['White', 'Black']], ['new-arrivals'], 'Lightweight running sneakers with responsive cushioning and breathable mesh upper.'],
            ['Graphic Print Tee', 'graphic-print-tee', 'Acme Basics', 'T-Shirts', ['new'], 2999, null, 210, 18, 'deny', true, ['Size' => ['S', 'M', 'L', 'XL']], ['t-shirts'], 'Bold graphic print on soft cotton. Express yourself with this statement piece.'],
            ['V-Neck Linen Tee', 'v-neck-linen-tee', 'Acme Basics', 'T-Shirts', ['popular'], 3499, null, 180, 12, 'deny', true, ['Size' => ['S', 'M', 'L'], 'Color' => ['Beige', 'Olive', 'Sky Blue']], ['t-shirts'], 'Lightweight linen blend v-neck. Perfect for warm summer days.'],
            ['Striped Polo Shirt', 'striped-polo-shirt', 'Acme Basics', 'T-Shirts', ['sale'], 2799, 3999, 250, 10, 'deny', true, ['Size' => ['S', 'M', 'L', 'XL']], ['t-shirts', 'sale'], 'Classic striped polo with a modern relaxed fit. Knitted collar and two-button placket.'],
            ['Cargo Pants', 'cargo-pants', 'Acme Workwear', 'Pants', ['popular'], 5499, null, 700, 14, 'deny', true, ['Size' => ['30', '32', '34', '36'], 'Color' => ['Khaki', 'Olive', 'Black']], ['pants-jeans'], 'Utility cargo pants with multiple pockets. Durable cotton twill construction.'],
            ['Chino Shorts', 'chino-shorts', 'Acme Basics', 'Pants', ['new', 'trending'], 3999, null, 350, 16, 'deny', true, ['Size' => ['30', '32', '34', '36'], 'Color' => ['Navy', 'Sand']], ['pants-jeans', 'new-arrivals'], 'Tailored chino shorts. Comfortable stretch fabric with a clean silhouette.'],
            ['Wide Leg Trousers', 'wide-leg-trousers', 'Acme Denim', 'Pants', ['sale'], 4999, 6999, 550, 7, 'deny', true, ['Size' => ['S', 'M', 'L']], ['pants-jeans', 'sale'], 'Relaxed wide leg trousers with a high waist. Flowing drape in premium woven fabric.'],
            ['Wool Scarf', 'wool-scarf', 'Acme Accessories', 'Accessories', ['popular'], 2999, null, 120, 30, 'deny', true, ['Color' => ['Grey', 'Burgundy', 'Navy']], [], 'Warm merino wool scarf. Soft hand feel, naturally breathable and temperature regulating.'],
            ['Canvas Tote Bag', 'canvas-tote-bag', 'Acme Accessories', 'Accessories', ['trending'], 1999, null, 300, 40, 'deny', true, ['Color' => ['Natural', 'Black']], [], 'Heavy-duty canvas tote bag with reinforced handles. Spacious enough for daily essentials.'],
            ['Bucket Hat', 'bucket-hat', 'Acme Accessories', 'Accessories', ['new', 'trending'], 2499, null, 80, 22, 'deny', true, ['Size' => ['S/M', 'L/XL'], 'Color' => ['Beige', 'Black', 'Olive']], ['new-arrivals'], 'Lightweight bucket hat for sun protection. Packable design, washed cotton twill.'],
            ['Unreleased Winter Jacket', 'unreleased-winter-jacket', 'Acme Outerwear', 'Jackets', ['limited'], 14999, null, 900, 0, 'deny', true, ['Size' => ['S', 'M', 'L', 'XL']], [], 'Upcoming winter collection piece. Insulated puffer jacket with water-resistant shell.', 'draft'],
            ['Discontinued Raincoat', 'discontinued-raincoat', 'Acme Outerwear', 'Jackets', [], 8999, null, 400, 3, 'deny', true, ['Size' => ['M', 'L']], [], 'Lightweight waterproof raincoat. This product has been discontinued.', 'archived'],
            ['Limited Edition Sneakers', 'limited-edition-sneakers', 'Acme Sport', 'Shoes', ['limited'], 15999, null, 650, 0, 'deny', true, ['Size' => ['EU 40', 'EU 42', 'EU 44']], [], 'Limited edition collaboration sneakers. Once they are gone, they are gone.'],
            ['Backorder Denim Jacket', 'backorder-denim-jacket', 'Acme Denim', 'Jackets', ['popular'], 9999, null, 750, 0, 'continue', true, ['Size' => ['S', 'M', 'L', 'XL']], [], 'Classic denim jacket. Currently on backorder - ships within 2-3 weeks.'],
            ['Gift Card', 'gift-card', 'Acme Fashion', 'Gift Cards', ['popular'], [2500, 5000, 10000], null, 0, 9999, 'deny', false, ['Amount' => ['25 EUR', '50 EUR', '100 EUR']], [], 'Digital gift card delivered via email. The perfect gift when you are not sure what to choose.'],
            ['Cashmere Overcoat', 'cashmere-overcoat', 'Acme Premium', 'Jackets', ['limited', 'new'], 49999, null, 1200, 3, 'deny', true, ['Size' => ['S', 'M', 'L'], 'Color' => ['Camel', 'Charcoal']], ['new-arrivals'], 'Luxurious cashmere-blend overcoat. Impeccable tailoring with silk lining.'],
        ];

        foreach ($products as $position => $definition) {
            $this->seedProduct($fashion, $definition, $position);
        }

        foreach ([
            ['Pro Laptop 15', 'pro-laptop-15', 'TechCorp', 'Laptops', ['featured'], [99999, 119999, 149999], null, 1800, 10, 'deny', true, ['Storage' => ['256GB', '512GB', '1TB']], ['featured'], 'Professional laptop with a brilliant 15-inch display.'],
            ['Wireless Headphones', 'wireless-headphones', 'AudioMax', 'Audio', ['featured'], 14999, null, 250, 25, 'deny', true, ['Color' => ['Black', 'Silver']], ['featured'], 'Immersive wireless audio with active noise cancellation.'],
            ['USB-C Cable 2m', 'usb-c-cable-2m', 'CablePro', 'Cables', ['accessory'], 1299, null, 50, 200, 'deny', true, [], ['accessories'], 'Durable two-metre USB-C charging and data cable.'],
            ['Mechanical Keyboard', 'mechanical-keyboard', 'KeyTech', 'Peripherals', ['featured'], 12999, null, 1100, 15, 'deny', true, ['Switch Type' => ['Red', 'Blue', 'Brown']], ['featured'], 'Precision mechanical keyboard for work and gaming.'],
            ['Monitor Stand', 'monitor-stand', 'DeskGear', 'Accessories', ['accessory'], 4999, null, 2500, 30, 'deny', true, [], ['accessories'], 'Ergonomic monitor stand with integrated storage.'],
        ] as $position => $definition) {
            $this->seedProduct($electronics, $definition, $position);
        }
    }

    /** @param array<int, mixed> $definition */
    private function seedProduct(Store $store, array $definition, int $productPosition): void
    {
        [$title, $handle, $vendor, $type, $tags, $prices, $compareAt, $weight, $inventory, $policy, $requiresShipping, $options, $collections, $description, $status] = array_pad($definition, 16, 'active');
        $product = Product::withoutGlobalScopes()->updateOrCreate(['store_id' => $store->id, 'handle' => $handle], [
            'title' => $title,
            'status' => $status,
            'description_html' => '<p>'.$description.'</p>',
            'vendor' => $vendor,
            'product_type' => $type,
            'tags' => $tags,
            'published_at' => $status === 'draft' ? null : ($status === 'archived' ? now()->subMonths(6) : now()),
        ]);

        if (! $product->variants()->exists()) {
            $groups = [];
            foreach ($options as $optionPosition => $values) {
                $option = $product->options()->create(['name' => $optionPosition, 'position' => count($groups)]);
                $groups[] = collect($values)->values()->map(fn (string $value, int $valuePosition) => $option->values()->create(['value' => $value, 'position' => $valuePosition]))->all();
            }
            $combinations = $groups === [] ? [[]] : $this->cartesian($groups);
            foreach ($combinations as $position => $combination) {
                $price = is_array($prices) ? (int) ($prices[$position] ?? end($prices)) : (int) $prices;
                $sku = $handle === 'gift-card'
                    ? ['ACME-GIFT-25', 'ACME-GIFT-50', 'ACME-GIFT-100'][$position]
                    : strtoupper('ACME-'.substr(preg_replace('/[^a-z0-9]/', '', $handle), 0, 8).'-'.($position + 1));
                $variant = $product->variants()->create([
                    'sku' => $sku,
                    'barcode' => null,
                    'price_amount' => $price,
                    'compare_at_amount' => $compareAt,
                    'currency' => 'EUR',
                    'weight_g' => $weight,
                    'requires_shipping' => $requiresShipping,
                    'is_default' => $position === 0,
                    'position' => $position,
                    'status' => 'active',
                ]);
                if ($combination !== []) {
                    $variant->optionValues()->sync(collect($combination)->pluck('id')->all());
                }
                InventoryItem::withoutGlobalScopes()->updateOrCreate(
                    ['variant_id' => $variant->id],
                    [
                        'store_id' => $store->id,
                        'quantity_on_hand' => $inventory,
                        'quantity_reserved' => 0,
                        'policy' => $policy,
                    ],
                );
            }
        }

        $collectionIds = Collection::withoutGlobalScopes()->where('store_id', $store->id)->whereIn('handle', $collections)->pluck('id', 'handle');
        $sync = [];
        foreach ($collections as $position => $collectionHandle) {
            if (isset($collectionIds[$collectionHandle])) {
                $sync[$collectionIds[$collectionHandle]] = ['position' => $productPosition];
            }
        }
        $product->collections()->sync($sync);
    }

    /** @param list<array<int, mixed>> $groups @return list<list<mixed>> */
    private function cartesian(array $groups): array
    {
        $result = [[]];
        foreach ($groups as $group) {
            $next = [];
            foreach ($result as $prefix) {
                foreach ($group as $value) {
                    $next[] = [...$prefix, $value];
                }
            }
            $result = $next;
        }

        return $result;
    }
}
