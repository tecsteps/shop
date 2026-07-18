<?php

namespace Database\Factories;

use App\Enums\VariantStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(fake()->bothify('SKU-####-??')),
            'barcode' => fake()->ean13(),
            'price_amount' => fake()->numberBetween(500, 20000),
            'compare_at_amount' => null,
            'currency' => 'EUR',
            'weight_g' => fake()->numberBetween(100, 2000),
            'requires_shipping' => true,
            'is_default' => false,
            'position' => 0,
            'status' => VariantStatus::Active,
        ];
    }

    public function withInventory(int $onHand = 100): static
    {
        return $this->afterCreating(function (ProductVariant $variant) use ($onHand): void {
            InventoryItem::factory()->create([
                'store_id' => $variant->product->store_id,
                'variant_id' => $variant->id,
                'quantity_on_hand' => $onHand,
            ]);
        });
    }
}
