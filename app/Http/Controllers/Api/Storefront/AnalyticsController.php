<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', 'in:page_view,add_to_cart,remove_from_cart,checkout_started,checkout_completed,search'],
            'events.*.session_id' => ['required', 'string', 'max:100'],
            'events.*.client_event_id' => ['required', 'string', 'max:100'],
            'events.*.occurred_at' => ['required', 'date'],
            'events.*.properties' => ['sometimes', 'array', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($this->depth((array) $value) > 3) {
                    $fail("The {$attribute} field may not be nested more than 3 levels.");
                }
            }],
        ]);
        $accepted = 0;
        $rejected = 0;
        foreach ($validated['events'] as $index => $event) {
            $occurredAt = Carbon::parse($event['occurred_at']);
            if ($occurredAt->lt(now()->subHour()) || $occurredAt->gt(now()->addHour())) {
                throw ValidationException::withMessages(["events.{$index}.occurred_at" => 'The event timestamp must be within one hour of the current time.']);
            }
            $record = $this->analytics->track(
                app('current_store'),
                $event['type'],
                $event['properties'] ?? [],
                $event['session_id'],
                auth('customer')->id(),
                $event['client_event_id'],
                $occurredAt,
            );
            $record?->wasRecentlyCreated ? $accepted++ : $rejected++;
        }

        return response()->json(['accepted' => $accepted, 'rejected' => $rejected], 202);
    }

    private function depth(array $value): int
    {
        $depth = 1;
        foreach ($value as $item) {
            if (is_array($item)) {
                $depth = max($depth, 1 + $this->depth($item));
            }
        }

        return $depth;
    }
}
