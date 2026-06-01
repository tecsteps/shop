<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Storefront analytics ingestion. Accepts a batch of client events, deduplicates
 * by `client_event_id` per store, and records each via {@see AnalyticsService}.
 * Authenticated customers (customer guard) have their id attached automatically.
 */
class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    /**
     * Batch-ingest analytics events. Duplicate client event ids are silently
     * dropped; the response reports accepted vs. rejected counts.
     */
    public function store(Request $request): JsonResponse
    {
        $store = app('current_store');

        $validated = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', 'string', Rule::in($this->analytics->ingestibleTypes())],
            'events.*.session_id' => ['required', 'string', 'max:100'],
            'events.*.client_event_id' => ['required', 'string', 'max:100'],
            'events.*.properties' => ['sometimes', 'array'],
            'events.*.occurred_at' => ['required', 'date'],
        ]);

        $customerId = $request->user('customer')?->id;

        $accepted = 0;
        $rejected = 0;

        foreach ($validated['events'] as $event) {
            $existing = $this->analytics->track(
                store: $store,
                type: $event['type'],
                properties: $event['properties'] ?? [],
                sessionId: $event['session_id'],
                customerId: $customerId,
                clientEventId: $event['client_event_id'],
                occurredAt: $event['occurred_at'],
            );

            // A returned record whose client_event_id pre-existed indicates a
            // silently-dropped duplicate.
            if ($existing->wasRecentlyCreated) {
                $accepted++;
            } else {
                $rejected++;
            }
        }

        return response()->json([
            'accepted' => $accepted,
            'rejected' => $rejected,
        ], 202);
    }
}
