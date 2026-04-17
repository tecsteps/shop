<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Api\Concerns\ResolvesApiContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Analytics\IngestAnalyticsEventsRequest;
use App\Models\AnalyticsEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsEventController extends Controller
{
    use ResolvesApiContext;

    public function store(IngestAnalyticsEventsRequest $request): JsonResponse
    {
        $storeId = $this->currentStoreId($request);
        $events = $request->validated('events');

        $service = $this->resolveService('App\\Services\\AnalyticsService');
        $accepted = 0;
        $rejected = 0;

        DB::transaction(function () use ($service, $storeId, $events, &$accepted, &$rejected): void {
            foreach ($events as $event) {
                if (! is_array($event)) {
                    $rejected++;

                    continue;
                }

                if ($service !== null && method_exists($service, 'track')) {
                    try {
                        $service->track($storeId, $event);
                        $accepted++;

                        continue;
                    } catch (\Throwable) {
                        // Fall back to direct persistence below.
                    }
                }

                $clientEventId = isset($event['client_event_id']) && is_string($event['client_event_id'])
                    ? trim($event['client_event_id'])
                    : null;

                if ($clientEventId !== null && $clientEventId !== '') {
                    $exists = AnalyticsEvent::query()
                        ->where('store_id', $storeId)
                        ->where('client_event_id', $clientEventId)
                        ->exists();

                    if ($exists) {
                        $rejected++;

                        continue;
                    }
                }

                AnalyticsEvent::query()->create([
                    'store_id' => $storeId,
                    'type' => (string) $event['type'],
                    'session_id' => (string) $event['session_id'],
                    'customer_id' => isset($event['customer_id']) ? (int) $event['customer_id'] : null,
                    'properties_json' => isset($event['properties']) && is_array($event['properties']) ? $event['properties'] : [],
                    'client_event_id' => $clientEventId,
                    'occurred_at' => $event['occurred_at'],
                    'created_at' => now(),
                ]);

                $accepted++;
            }
        });

        return response()->json([
            'accepted' => $accepted,
            'rejected' => $rejected,
        ], 202);
    }
}
