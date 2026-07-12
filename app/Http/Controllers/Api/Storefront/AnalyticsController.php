<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events' => ['required', 'array', 'max:100'],
            'events.*.type' => ['required', 'in:page_view,product_view,add_to_cart,remove_from_cart,checkout_started,checkout_completed,search'],
            'events.*.session_id' => ['required', 'string', 'max:255'],
            'events.*.client_event_id' => ['required', 'string', 'max:255'],
            'events.*.occurred_at' => ['required', 'date'],
            'events.*.properties' => ['sometimes', 'array'],
        ]);
        foreach ($validated['events'] as $event) {
            $this->analytics->track(
                app('current_store'),
                $event['type'],
                $event['properties'] ?? [],
                $event['session_id'],
                auth('customer')->id(),
                $event['client_event_id'],
                Carbon::parse($event['occurred_at']),
            );
        }

        return response()->json(['accepted' => count($validated['events'])], 202);
    }
}
