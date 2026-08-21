<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnalyticsEventsRequest;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class StorefrontAnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function store(StoreAnalyticsEventsRequest $request): JsonResponse
    {
        $events = $request->validated()['events'];
        $stored = [];
        foreach ($events as $event) {
            $stored[] = $this->analytics->track(app('current_store'), $event['type'], $event['properties'] ?? [], $event['session_id'] ?? ($request->hasSession() ? $request->session()->getId() : null), $request->user('customer')?->getKey(), $event['client_event_id'] ?? null, isset($event['occurred_at']) ? new \DateTimeImmutable($event['occurred_at']) : null);
        }

        return response()->json(['accepted' => count($stored), 'rejected' => 0], 202);
    }
}
