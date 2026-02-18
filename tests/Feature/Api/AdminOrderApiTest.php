<?php

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;

beforeEach(function () {
    $this->context = createStoreContext();
    $this->store = $this->context['store'];
    $this->user = $this->context['user'];
});

it('lists orders with authentication', function () {
    Order::factory()->count(3)->create(['store_id' => $this->store->id]);

    $token = $this->user->createToken('test', ['read-orders'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->getJson("/api/admin/v1/stores/{$this->store->id}/orders");

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total']]);

    expect($response->json('meta.total'))->toBe(3);
});

it('retrieves a single order', function () {
    $order = Order::factory()->create(['store_id' => $this->store->id]);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
    ]);

    Payment::factory()->create(['order_id' => $order->id, 'amount' => $order->total_amount]);

    $token = $this->user->createToken('test', ['read-orders'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->getJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['id', 'order_number', 'status', 'lines', 'payments']]);
});

it('filters orders by status', function () {
    Order::factory()->paid()->count(2)->create(['store_id' => $this->store->id]);
    Order::factory()->pending()->count(3)->create(['store_id' => $this->store->id]);

    $token = $this->user->createToken('test', ['read-orders'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->getJson("/api/admin/v1/stores/{$this->store->id}/orders?status=paid");

    $response->assertStatus(200);
    expect($response->json('meta.total'))->toBe(2);
});

it('creates a fulfillment via API', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $line = OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $token = $this->user->createToken('test', ['write-orders'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/fulfillments", [
            'line_items' => [
                ['order_line_id' => $line->id, 'quantity' => 2],
            ],
            'tracking_company' => 'DHL',
            'tracking_number' => '1234567890',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'order_id', 'status', 'tracking_company', 'tracking_number']]);
});

it('creates a refund via API', function () {
    $order = Order::factory()->paid()->create([
        'store_id' => $this->store->id,
        'total_amount' => 5000,
    ]);

    Payment::factory()->captured()->create([
        'order_id' => $order->id,
        'amount' => 5000,
    ]);

    $token = $this->user->createToken('test', ['write-orders'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/refunds", [
            'amount' => 2000,
            'reason' => 'Customer request',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'order_id', 'amount', 'reason', 'status']]);
});

it('requires write-orders ability for mutations', function () {
    $order = Order::factory()->paid()->create(['store_id' => $this->store->id]);

    $product = Product::factory()->active()->create(['store_id' => $this->store->id]);
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $line = OrderLine::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'variant_id' => $variant->id,
    ]);

    $token = $this->user->createToken('test', ['read-orders'])->plainTextToken;

    $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->postJson("/api/admin/v1/stores/{$this->store->id}/orders/{$order->id}/fulfillments", [
            'line_items' => [
                ['order_line_id' => $line->id, 'quantity' => 1],
            ],
        ]);

    $response->assertStatus(403);
});
