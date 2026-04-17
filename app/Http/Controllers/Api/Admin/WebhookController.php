<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\WebhookTopic;
use App\Http\Controllers\Controller;
use App\Http\Resources\WebhookSubscriptionResource;
use App\Models\Store;
use App\Models\WebhookSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request, int $storeId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);

        $subs = WebhookSubscription::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->orderByDesc('id')
            ->get();

        return WebhookSubscriptionResource::collection($subs)->response();
    }

    public function store(Request $request, int $storeId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);

        $validated = $request->validate([
            'event_type' => ['required', 'string', 'in:'.implode(',', WebhookTopic::values())],
            'target_url' => ['required', 'url', 'max:2048'],
            'signing_secret' => ['required', 'string', 'min:8'],
            'status' => ['nullable', 'string', 'in:active,paused,disabled'],
        ]);

        $subscription = WebhookSubscription::query()->create([
            'store_id' => $store->getKey(),
            'event_type' => $validated['event_type'],
            'target_url' => $validated['target_url'],
            'signing_secret_encrypted' => $validated['signing_secret'],
            'status' => $validated['status'] ?? 'active',
            'created_at' => now(),
        ]);

        return (new WebhookSubscriptionResource($subscription))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, int $storeId, int $subscriptionId): JsonResponse
    {
        $store = $this->resolveStore($request, $storeId);

        $subscription = WebhookSubscription::query()
            ->withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->findOrFail($subscriptionId);

        $subscription->delete();

        return response()->json(null, 204);
    }

    protected function resolveStore(Request $request, int $storeId): Store
    {
        $user = $request->user();
        $store = Store::query()->findOrFail($storeId);

        if ($user === null || ! $user->stores()->wherePivot('store_id', $store->getKey())->exists()) {
            abort(403);
        }

        app()->instance('current_store', $store);

        return $store;
    }
}
