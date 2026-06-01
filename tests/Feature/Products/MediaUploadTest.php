<?php

use App\Enums\MediaStatus;
use App\Jobs\ProcessMediaUpload;
use App\Livewire\Admin\Products\MediaManager;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $context = createStoreContext();
    $this->store = $context['store'];
    $this->product = Product::factory()->create(['store_id' => $this->store->id]);
});

it('uploads an image for a product', function () {
    Queue::fake();

    Livewire::test(MediaManager::class, ['product' => $this->product])
        ->set('upload', UploadedFile::fake()->image('tee.jpg', 800, 800))
        ->call('save')
        ->assertHasNoErrors();

    $media = $this->product->media()->first();

    expect($media)->not->toBeNull()
        ->and($media->status)->toBe(MediaStatus::Processing);

    Queue::assertPushed(ProcessMediaUpload::class);
});

it('processes uploaded image and generates variants', function () {
    $file = UploadedFile::fake()->image('tee.jpg', 800, 800);
    $storageKey = $file->store('products', 'public');

    $media = $this->product->media()->create([
        'type' => 'image',
        'storage_key' => $storageKey,
        'status' => MediaStatus::Processing->value,
    ]);

    (new ProcessMediaUpload($media->id))->handle();

    $media->refresh();
    $base = substr($storageKey, 0, -strlen('.jpg'));

    expect($media->status)->toBe(MediaStatus::Ready)
        ->and($media->width)->toBe(800)
        ->and($media->height)->toBe(800);

    Storage::disk('public')->assertExists("{$base}-thumbnail.jpg");
    Storage::disk('public')->assertExists("{$base}-medium.jpg");
    Storage::disk('public')->assertExists("{$base}-large.jpg");
});

it('rejects non-image file types', function () {
    Livewire::test(MediaManager::class, ['product' => $this->product])
        ->set('upload', UploadedFile::fake()->create('notes.txt', 16, 'text/plain'))
        ->call('save')
        ->assertHasErrors(['upload']);

    expect($this->product->media()->count())->toBe(0);
});

it('sets alt text on media', function () {
    $media = ProductMedia::factory()->create(['product_id' => $this->product->id, 'alt_text' => null]);

    Livewire::test(MediaManager::class, ['product' => $this->product])
        ->call('updateAltText', $media->id, 'A blue t-shirt');

    expect($media->fresh()->alt_text)->toBe('A blue t-shirt');
});

it('reorders media positions', function () {
    $media = ProductMedia::factory()->count(3)->sequence(
        ['position' => 0],
        ['position' => 1],
        ['position' => 2],
    )->create(['product_id' => $this->product->id]);

    [$a, $b, $c] = [$media[0], $media[1], $media[2]];

    Livewire::test(MediaManager::class, ['product' => $this->product])
        ->call('reorder', [$c->id, $a->id, $b->id]);

    expect($c->fresh()->position)->toBe(0)
        ->and($a->fresh()->position)->toBe(1)
        ->and($b->fresh()->position)->toBe(2);
});

it('deletes media and removes file from storage', function () {
    $file = UploadedFile::fake()->image('tee.jpg', 400, 400);
    $storageKey = $file->store('products', 'public');
    Storage::disk('public')->assertExists($storageKey);

    $media = ProductMedia::factory()->create([
        'product_id' => $this->product->id,
        'storage_key' => $storageKey,
    ]);

    Livewire::test(MediaManager::class, ['product' => $this->product])
        ->call('delete', $media->id);

    expect(ProductMedia::find($media->id))->toBeNull();
    Storage::disk('public')->assertMissing($storageKey);
});
