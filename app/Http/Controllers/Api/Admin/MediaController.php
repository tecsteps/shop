<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMediaUpload;
use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

final class MediaController extends Controller
{
    public function presign(Request $request, int $storeId, int $productId): JsonResponse
    {
        $data = $request->validate([
            'filename' => ['required', 'string', 'max:255', 'regex:/\.(jpe?g|png|webp|avif|mp4)$/i'],
            'content_type' => ['required', 'in:image/jpeg,image/png,image/webp,image/avif,video/mp4'],
            'byte_size' => ['required', 'integer', 'min:1'],
        ]);
        $maximum = $data['content_type'] === 'video/mp4' ? 500 * 1024 * 1024 : 50 * 1024 * 1024;
        abort_if((int) $data['byte_size'] > $maximum, 422, 'The uploaded file is too large.');

        $product = Product::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($productId);
        $extension = mb_strtolower((string) pathinfo($data['filename'], PATHINFO_EXTENSION));
        $storageKey = "stores/{$storeId}/products/{$product->id}/media/".Str::uuid().".{$extension}";
        $media = $product->media()->create([
            'type' => $data['content_type'] === 'video/mp4' ? 'video' : 'image',
            'storage_key' => $storageKey,
            'mime_type' => $data['content_type'],
            'byte_size' => $data['byte_size'],
            'position' => ((int) $product->media()->max('position')) + 1,
            'status' => 'processing',
        ]);
        $expires = now()->addMinutes(10);

        return response()->json([
            'upload_url' => URL::temporarySignedRoute('api.media.upload', $expires, ['media' => $media->id]),
            'method' => 'PUT',
            'headers' => ['Content-Type' => $data['content_type']],
            'storage_key' => $storageKey,
            'media_id' => $media->id,
            'expires_at' => $expires->toIso8601String(),
        ], 201);
    }

    public function upload(Request $request, ProductMedia $media): JsonResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'The upload URL is invalid or expired.');
        $contents = $request->getContent();
        abort_if($contents === '', 422, 'The upload body is empty.');
        abort_if(strlen($contents) > (int) $media->byte_size, 422, 'The upload exceeds the declared size.');

        Storage::disk('public')->put($media->storage_key, $contents);
        if (($media->type instanceof \BackedEnum ? $media->type->value : $media->type) === 'video') {
            $media->update(['status' => 'ready', 'byte_size' => strlen($contents)]);
        } else {
            ProcessMediaUpload::dispatch($media);
        }

        return response()->json(['data' => $media->refresh()], 202);
    }
}
