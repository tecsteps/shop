<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analyticsService) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', 'string', 'in:page_view,product_view,add_to_cart,remove_from_cart,checkout_started,checkout_completed,search'],
            'events.*.session_id' => ['required', 'string', 'max:100'],
            'events.*.client_event_id' => ['required', 'string', 'max:100'],
        ]);

        $accepted = 0;

        foreach ($validated['events'] as $event) {
            $this->analyticsService->track(
                app('current_store'),
                $event['type'],
                $event['properties'] ?? [],
                $event['session_id'],
                null,
                $event['client_event_id'],
                isset($event['occurred_at']) ? \Carbon\Carbon::parse($event['occurred_at']) : null,
            );
            $accepted++;
        }

        return response()->json(['accepted' => $accepted, 'rejected' => 0], 202);
    }
}
