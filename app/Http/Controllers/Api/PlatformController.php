<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrganizationRequest;
use App\Http\Requests\CreatePlatformStoreRequest;
use App\Http\Requests\PresignMediaUploadRequest;
use App\Http\Requests\StoreInvitationRequest;
use App\Jobs\ProcessMediaUpload;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Store;
use App\Models\StoreInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PlatformController extends Controller
{
    public function storeOrganization(CreateOrganizationRequest $request): JsonResponse
    {
        $this->assertPlatformAdministrator();
        $data = $request->validated();
        $slug = Str::slug($data['name']);
        $suffix = 1;
        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = Str::slug($data['name']).'-'.$suffix++;
        }

        return response()->json(['data' => Organization::create([...$data, 'slug' => $slug, 'status' => 'active'])], 201);
    }

    public function storeStore(CreatePlatformStoreRequest $request): JsonResponse
    {
        $this->assertPlatformAdministrator();
        $data = $request->validated();
        $store = Store::create([...$data, 'status' => 'active']);
        $store->users()->syncWithoutDetaching([
            request()->user('sanctum')->getKey() => ['role' => 'owner'],
        ]);

        return response()->json(['data' => $store], 201);
    }

    public function invite(StoreInvitationRequest $request, int $storeId): JsonResponse
    {
        $store = $this->store($storeId);
        $data = $request->validated();
        $user = User::query()->where('email', $data['email'])->first();

        if ($user !== null && $store->users()->whereKey($user->getKey())->exists()) {
            return response()->json(['message' => 'The user is already a member of this store.'], 409);
        }

        $invitation = StoreInvitation::query()->updateOrCreate(
            ['store_id' => $store->getKey(), 'email' => $data['email']],
            ['role' => $data['role'], 'invited_at' => now(), 'expires_at' => now()->addDays(7), 'accepted_at' => null],
        );

        return response()->json(['data' => ['email' => $invitation->email, 'role' => $invitation->role, 'invited_at' => $invitation->invited_at, 'expires_at' => $invitation->expires_at]], 201);
    }

    public function me(int $storeId): JsonResponse
    {
        $store = $this->store($storeId);
        $user = request()->user('sanctum');
        $membership = $user?->stores()->whereKey($store->getKey())->first();
        abort_unless($membership !== null, 403);

        return response()->json(['data' => [
            'user_id' => $user->getKey(),
            'store_id' => $store->getKey(),
            'role' => $membership->pivot->role->value ?? $membership->pivot->role,
            'email' => $user->email,
            'name' => $user->name,
            'permissions' => $this->permissionsFor($membership->pivot->role->value ?? (string) $membership->pivot->role),
        ]]);
    }

    public function presignMediaUpload(PresignMediaUploadRequest $request, int $storeId, int $productId): JsonResponse
    {
        $store = $this->store($storeId);
        $product = Product::withoutGlobalScopes()->where('store_id', $store->getKey())->findOrFail($productId);
        $data = $request->validated();
        $extension = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        $storageKey = 'stores/'.$store->getKey().'/products/'.$product->getKey().'/media/'.Str::uuid().'.'.$extension;
        $disk = Storage::disk('public');
        $media = ProductMedia::query()->create([
            'product_id' => $product->getKey(),
            'type' => str_starts_with($data['content_type'], 'video/') ? 'video' : 'image',
            'path' => $storageKey,
            'storage_key' => $storageKey,
            'url' => $disk->url($storageKey),
            'mime_type' => $data['content_type'],
            'byte_size' => $data['byte_size'],
            'status' => 'processing',
            'position' => (int) $product->media()->max('position') + 1,
        ]);

        $uploadUrl = $disk->url($storageKey);
        if (method_exists($disk, 'temporaryUploadUrl')) {
            try {
                $uploadUrl = $disk->temporaryUploadUrl($storageKey, now()->addMinutes(10), ['ContentType' => $data['content_type']]);
            } catch (Throwable) {
                // Local development disks do not support presigned uploads.
            }
        }

        if (config('queue.default') !== 'sync') {
            ProcessMediaUpload::dispatch($media)->delay(now()->addMinutes(10));
        }

        return response()->json(['upload_url' => $uploadUrl, 'method' => 'PUT', 'headers' => ['Content-Type' => $data['content_type']], 'storage_key' => $storageKey, 'media_id' => $media->getKey(), 'expires_at' => now()->addMinutes(10)], 201);
    }

    public function completeMediaUpload(int $storeId, int $productId, int $mediaId): JsonResponse
    {
        $store = $this->store($storeId);
        $product = Product::withoutGlobalScopes()->where('store_id', $store->getKey())->findOrFail($productId);
        $media = $product->media()->whereKey($mediaId)->firstOrFail();
        $disk = Storage::disk('public');
        $storageKey = $media->storage_key ?: $media->path;

        abort_unless($storageKey !== null && $disk->exists($storageKey), 409, 'The media upload has not completed.');
        ProcessMediaUpload::dispatch($media);

        return response()->json(['data' => $media->refresh()], 202);
    }

    private function store(int $storeId): Store
    {
        return Store::query()->findOrFail($storeId);
    }

    private function assertPlatformAdministrator(): void
    {
        abort_unless(request()->user('sanctum')?->isPlatformAdmin(), 403, 'Platform administrator access is required.');
    }

    /** @return array<int, string> */
    private function permissionsFor(string $role): array
    {
        return match ($role) {
            'owner', 'admin' => ['read-products', 'write-products', 'read-orders', 'write-orders', 'read-settings', 'write-settings', 'read-customers', 'write-customers', 'read-analytics', 'read-content', 'write-content'],
            'staff' => ['read-products', 'write-products', 'read-orders', 'write-orders', 'read-settings', 'read-customers'],
            default => ['read-products', 'read-orders', 'read-customers'],
        };
    }
}
