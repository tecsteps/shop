<?php

namespace App\Livewire\Admin\Products;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Admin media manager for a single product: upload images, edit alt text,
 * reorder, and delete.
 *
 * Uploads land on the `public` disk in `processing` state, then a
 * {@see ProcessMediaUpload} job generates resized renditions and flips the
 * record to `ready`/`failed`. Uses Livewire's {@see WithFileUploads} trait.
 */
class MediaManager extends Component
{
    use WithFileUploads;

    public Product $product;

    /**
     * The pending file upload bound to the file input.
     *
     * @var TemporaryUploadedFile|null
     */
    public $upload;

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    /**
     * Validation rules for the bound upload.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'upload' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:10240'],
        ];
    }

    /**
     * Store the uploaded image and dispatch processing.
     */
    public function save(): void
    {
        $this->validate();

        $storageKey = $this->upload->store('products', 'public');

        $media = $this->product->media()->create([
            'type' => MediaType::Image->value,
            'storage_key' => $storageKey,
            'mime_type' => $this->upload->getMimeType(),
            'byte_size' => $this->upload->getSize(),
            'position' => (int) $this->product->media()->max('position') + 1,
            'status' => MediaStatus::Processing->value,
        ]);

        $this->upload = null;

        ProcessMediaUpload::dispatch($media->id);
    }

    /**
     * Update a media record's alt text.
     */
    public function updateAltText(int $mediaId, string $altText): void
    {
        $this->mediaQuery()->whereKey($mediaId)->update(['alt_text' => $altText]);
    }

    /**
     * Persist a new ordering for the product's media.
     *
     * @param  list<int>  $orderedIds  Media ids in their desired display order.
     */
    public function reorder(array $orderedIds): void
    {
        foreach (array_values($orderedIds) as $position => $mediaId) {
            $this->mediaQuery()->whereKey($mediaId)->update(['position' => $position]);
        }
    }

    /**
     * Delete a media record and remove its files from disk.
     */
    public function delete(int $mediaId): void
    {
        $media = $this->mediaQuery()->find($mediaId);

        if ($media === null) {
            return;
        }

        $this->deleteFiles($media);
        $media->delete();
    }

    /**
     * Remove the original and all generated renditions from the public disk.
     */
    private function deleteFiles(ProductMedia $media): void
    {
        $disk = Storage::disk('public');
        $disk->delete($media->storage_key);

        $extension = pathinfo($media->storage_key, PATHINFO_EXTENSION);
        $base = $extension !== '' ? substr($media->storage_key, 0, -(strlen($extension) + 1)) : $media->storage_key;

        foreach (['thumbnail', 'medium', 'large'] as $size) {
            $disk->delete("{$base}-{$size}.jpg");
        }
    }

    /**
     * Base query for media belonging to the managed product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<ProductMedia, Product>
     */
    private function mediaQuery()
    {
        return $this->product->media();
    }

    public function render()
    {
        return view('livewire.admin.products.media-manager', [
            'media' => $this->product->media()->get(),
        ]);
    }
}
