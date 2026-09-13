<?php

namespace App\Services;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Local-disk product media handling (spec 05 section 15.1, roadmap tech decision: local filesystem).
 */
class MediaService
{
    public function upload(Product $product, UploadedFile $file, ?string $altText = null): ProductMedia
    {
        $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension() ?: 'bin');
        $storageKey = "media/{$product->getKey()}/".((string) Str::uuid()).".{$extension}";

        Storage::disk('public')->putFileAs(
            dirname($storageKey),
            $file,
            basename($storageKey)
        );

        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $width = null;
        $height = null;

        if (str_starts_with($mimeType, 'image/')) {
            $dimensions = @getimagesize($file->getRealPath());

            if ($dimensions !== false) {
                [$width, $height] = $dimensions;
            }
        }

        $media = ProductMedia::query()->create([
            'product_id' => $product->getKey(),
            'type' => str_starts_with($mimeType, 'video/') ? MediaType::Video : MediaType::Image,
            'storage_key' => $storageKey,
            'alt_text' => $altText,
            'width' => $width,
            'height' => $height,
            'mime_type' => $mimeType,
            'byte_size' => $file->getSize(),
            'position' => ($product->productMedia()->max('position') ?? -1) + 1,
            'status' => MediaStatus::Processing,
            'created_at' => now(),
        ]);

        ProcessMediaUpload::dispatch($media->getKey());

        return $media->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateMedia(ProductMedia $media, array $data): ProductMedia
    {
        if (array_key_exists('alt_text', $data)) {
            $media->alt_text = $data['alt_text'];
        }

        if (array_key_exists('position', $data) && $data['position'] !== null) {
            $media->position = $data['position'];
        }

        $media->save();

        return $media->refresh();
    }

    public function deleteMedia(ProductMedia $media): void
    {
        $disk = Storage::disk('public');

        $disk->delete($media->storage_key);
        $disk->deleteDirectory("media/{$media->product_id}/{$media->getKey()}");

        $media->delete();
    }
}
