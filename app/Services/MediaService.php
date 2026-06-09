<?php

namespace App\Services;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaService
{
    /**
     * Store an uploaded image on the public disk, create the media record in
     * "processing" status, and dispatch the resize job.
     *
     * @throws ValidationException
     */
    public function attach(Product $product, UploadedFile $file): ProductMedia
    {
        if (! str_starts_with((string) $file->getMimeType(), 'image/')) {
            throw ValidationException::withMessages([
                'file' => 'Only image uploads are supported.',
            ]);
        }

        $storageKey = $file->store("media/products/{$product->getKey()}", 'public');

        $media = $product->media()->create([
            'type' => MediaType::Image,
            'storage_key' => $storageKey,
            'mime_type' => $file->getMimeType(),
            'byte_size' => $file->getSize(),
            'position' => $this->nextPosition($product),
            'status' => MediaStatus::Processing,
        ]);

        ProcessMediaUpload::dispatch($media);

        return $media;
    }

    public function updateAltText(ProductMedia $media, ?string $altText): ProductMedia
    {
        $media->update(['alt_text' => $altText]);

        return $media;
    }

    /**
     * Reorder the product's media by the given ordered list of media ids.
     *
     * @param  list<int>  $orderedMediaIds
     */
    public function reorder(Product $product, array $orderedMediaIds): void
    {
        foreach ($orderedMediaIds as $position => $mediaId) {
            $product->media()
                ->whereKey($mediaId)
                ->update(['position' => $position]);
        }
    }

    /**
     * Delete the media record and remove the original plus all derived files
     * from storage.
     */
    public function delete(ProductMedia $media): void
    {
        $disk = Storage::disk('public');

        $keys = [
            $media->storage_key,
            ...array_map(
                fn (string $size): string => $media->derivedStorageKey($size),
                array_keys(ProcessMediaUpload::SIZES),
            ),
        ];

        $disk->delete($keys);

        $media->delete();
    }

    private function nextPosition(Product $product): int
    {
        $maxPosition = $product->media()->max('position');

        return $maxPosition === null ? 0 : (int) $maxPosition + 1;
    }
}
