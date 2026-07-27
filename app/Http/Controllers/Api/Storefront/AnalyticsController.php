<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Storefront analytics event ingestion (spec 02 §2.6, spec 05 §14.1).
 */
class AnalyticsController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    /**
     * POST /api/storefront/v1/analytics/events — batch submit events.
     *
     * Duplicates (already-recorded client_event_ids) are silently dropped
     * and counted as accepted: from the client's perspective they are
     * acknowledged and must not be retried.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', 'string', Rule::in(AnalyticsService::EVENT_TYPES)],
            'events.*.session_id' => ['required', 'string', 'max:100'],
            'events.*.client_event_id' => ['required', 'string', 'max:100'],
            'events.*.occurred_at' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $occurredAt = Carbon::parse($value);

                    if ($occurredAt->greaterThan(now()->addHour()) || $occurredAt->lessThan(now()->subHour())) {
                        $fail('The '.$attribute.' must be within one hour of the current time.');
                    }
                },
            ],
            'events.*.properties' => [
                'nullable',
                'array',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_array($value) && $this->arrayDepth($value) > 3) {
                        $fail('The '.$attribute.' must not be more than 3 levels deep.');
                    }
                },
            ],
        ]);

        /** @var Store $store */
        $store = app('current_store');

        $accepted = 0;
        $rejected = 0;

        foreach ($validated['events'] as $event) {
            try {
                $this->analytics->track(
                    $store,
                    $event['type'],
                    $event['properties'] ?? [],
                    $event['session_id'],
                    null,
                    $event['client_event_id'],
                    $event['occurred_at'],
                );
                $accepted++;
            } catch (\Throwable) {
                // A single malformed row must not fail the whole batch.
                $rejected++;
            }
        }

        return response()->json(['accepted' => $accepted, 'rejected' => $rejected], 202);
    }

    /**
     * Nesting depth of an associative/array structure (root level = 1).
     */
    private function arrayDepth(array $array): int
    {
        $depth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = max($depth, 1 + $this->arrayDepth($value));
            }
        }

        return $depth;
    }
}
