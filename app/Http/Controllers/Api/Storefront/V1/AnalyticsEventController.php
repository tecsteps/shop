<?php

namespace App\Http\Controllers\Api\Storefront\V1;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnalyticsEventController extends Controller
{
    public function __construct(
        protected CurrentStore $currentStore,
        protected AnalyticsService $analytics,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events' => 'required|array|min:1|max:50',
            'events.*.type' => ['required', 'string', Rule::in(AnalyticsService::EVENT_TYPES)],
            'events.*.session_id' => 'required|string|max:100',
            'events.*.client_event_id' => 'required|string|max:100',
            'events.*.properties' => 'nullable|array',
            'events.*.occurred_at' => 'required|date',
        ]);

        foreach ($validated['events'] as $index => $event) {
            $occurredAt = now()->parse($event['occurred_at']);

            if ($occurredAt->isFuture() || $occurredAt->lt(now()->subHour()->subMinute())) {
                return response()->json([
                    'message' => 'The selected events.'.$index.'.occurred_at is invalid.',
                    'errors' => ['events.'.$index.'.occurred_at' => ['The occurred at field must not be more than 1 hour in the past or in the future.']],
                ], 422);
            }
        }

        $store = $this->currentStore->get();

        abort_if($store === null, 404);

        $result = $this->analytics->recordBatch($store, $validated['events']);

        return response()->json($result, 202);
    }
}
