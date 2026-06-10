<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AnalyticsEventController extends Controller
{
    public function __construct(protected AnalyticsService $analytics) {}

    /**
     * POST /api/storefront/v1/analytics/events
     *
     * Batch event ingestion (spec 02 section 2.6). Events are deduplicated
     * per store by client_event_id; duplicates count as rejected. Events
     * whose occurred_at is more than an hour in the past or in the future
     * fail validation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', 'string', Rule::in(AnalyticsService::EVENT_TYPES)],
            'events.*.session_id' => ['required', 'string', 'max:100'],
            'events.*.client_event_id' => ['required', 'string', 'max:100'],
            'events.*.properties' => ['sometimes', 'array'],
            'events.*.occurred_at' => [
                'required',
                'date',
                'after_or_equal:'.now()->subHour()->toIso8601String(),
                'before_or_equal:'.now()->addHour()->toIso8601String(),
            ],
        ]);

        $store = app('current_store');
        $customerId = $request->user('customer')?->getKey();

        $accepted = 0;
        $rejected = 0;

        foreach ($validated['events'] as $event) {
            $tracked = $this->analytics->track(
                $store,
                $event['type'],
                $event['properties'] ?? [],
                $event['session_id'],
                $customerId,
                $event['client_event_id'],
                Carbon::parse($event['occurred_at'])->toIso8601String(),
            );

            $tracked !== null ? $accepted++ : $rejected++;
        }

        return response()->json([
            'accepted' => $accepted,
            'rejected' => $rejected,
        ], 202);
    }
}
