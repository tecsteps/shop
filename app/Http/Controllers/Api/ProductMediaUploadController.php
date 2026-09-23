<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductMediaUploadController extends Controller
{
    /**
     * Create a short-lived upload target for product media.
     */
    public function presign(Request $request, int $storeId, int $productId): JsonResponse
    {
        $product = Product::query()->where('store_id', $storeId)->findOrFail($productId);
        Gate::authorize('update', $product);

        $validated = $request->validate([
            'filename' => ['required', 'string', 'max:255', 'regex:/\A[a-zA-Z0-9][a-zA-Z0-9._ -]*\.(?:jpe?g|png|webp|avif|mp4)\z/i'],
            'content_type' => ['required', 'string', Rule::in(['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'video/mp4'])],
            'byte_size' => ['required', 'integer', 'min:1'],
        ]);
        $extensionsByMime = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/webp' => ['webp'],
            'image/avif' => ['avif'],
            'video/mp4' => ['mp4'],
        ];
        $extension = Str::lower(pathinfo($validated['filename'], PATHINFO_EXTENSION));
        $contentType = $validated['content_type'];

        if (! in_array($extension, $extensionsByMime[$contentType], true)) {
            throw ValidationException::withMessages(['filename' => 'The filename extension must match the content type.']);
        }

        $isVideo = $contentType === 'video/mp4';
        $maximumBytes = (int) config($isVideo ? 'shop.media.video_max_bytes' : 'shop.media.image_max_bytes');

        if ($validated['byte_size'] > $maximumBytes) {
            throw ValidationException::withMessages(['byte_size' => 'The media file exceeds the configured size limit.']);
        }

        $expiresAt = now()->addMinutes(max((int) config('shop.media.signed_upload_minutes'), 1));
        $storageKey = "stores/{$storeId}/products/{$productId}/media/".Str::uuid().'.'.$extension;
        $media = $product->media()->create([
            'type' => $isVideo ? 'video' : 'image',
            'storage_key' => $storageKey,
            'mime_type' => $contentType,
            'byte_size' => $validated['byte_size'],
            'position' => (int) $product->media()->max('position') + 1,
            'status' => 'processing',
            'created_at' => now(),
        ]);

        return response()->json([
            'upload_url' => URL::temporarySignedRoute('api.product-media.upload', $expiresAt, ['mediaId' => $media->id]),
            'method' => 'PUT',
            'headers' => ['Content-Type' => $contentType],
            'storage_key' => $storageKey,
            'media_id' => $media->id,
            'expires_at' => $expiresAt->toISOString(),
        ], 201);
    }

    /**
     * Store media bytes using the signed upload target.
     */
    public function upload(Request $request, int $mediaId): JsonResponse
    {
        $media = ProductMedia::query()->findOrFail($mediaId);

        if ($media->status !== 'processing') {
            return response()->json(['message' => 'This upload URL has already been used.'], 409);
        }

        if ($request->header('Content-Type') !== $media->mime_type) {
            throw ValidationException::withMessages(['content_type' => 'The content type does not match the upload request.']);
        }

        $maximumBytes = (int) config($media->type === 'video' ? 'shop.media.video_max_bytes' : 'shop.media.image_max_bytes');
        $temporaryPath = tempnam(sys_get_temp_dir(), 'shop-media-');

        if ($temporaryPath === false) {
            return response()->json(['message' => 'The upload could not be processed.'], 500);
        }

        $target = fopen($temporaryPath, 'wb');
        $source = $request->getContent(true);
        $stored = false;
        $dimensions = false;
        $actualBytes = 0;

        try {
            if (! is_resource($target) || ! is_resource($source)) {
                return response()->json(['message' => 'The upload could not be processed.'], 500);
            }

            $actualBytes = stream_copy_to_stream($source, $target, $maximumBytes + 1);

            if ($actualBytes === false || $actualBytes > $maximumBytes) {
                throw ValidationException::withMessages(['byte_size' => 'The media file exceeds the configured size limit.']);
            }

            if ($actualBytes !== (int) $media->byte_size) {
                throw ValidationException::withMessages(['byte_size' => 'The uploaded byte count does not match the requested byte size.']);
            }

            fflush($target);
            fclose($target);
            $target = null;

            if ((new \finfo(FILEINFO_MIME_TYPE))->file($temporaryPath) !== $media->mime_type) {
                throw ValidationException::withMessages(['content_type' => 'The uploaded file content does not match its content type.']);
            }

            $dimensions = $media->type === 'image' ? @getimagesize($temporaryPath) : false;
            $stream = fopen($temporaryPath, 'rb');

            if (! is_resource($stream)) {
                return response()->json(['message' => 'The upload could not be processed.'], 500);
            }

            try {
                $stored = Storage::disk('public')->put($media->storage_key, $stream);
            } finally {
                fclose($stream);
            }
        } finally {
            if (is_resource($target)) {
                fclose($target);
            }

            if (is_resource($source)) {
                fclose($source);
            }

            @unlink($temporaryPath);
        }

        if (! $stored) {
            $media->forceFill(['status' => 'failed'])->save();

            return response()->json(['message' => 'The media file could not be stored.'], 500);
        }

        $media->forceFill([
            'status' => 'ready',
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
        ])->save();

        return response()->json(['media' => $media->refresh()]);
    }
}
