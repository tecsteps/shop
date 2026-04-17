<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsEventsController extends Controller
{
    public function store(Request $request, AnalyticsService $analytics): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:64'],
            'properties' => ['nullable', 'array'],
            'client_event_id' => ['nullable', 'string', 'max:128'],
        ]);

        $store = app('current_store');

        $event = $analytics->track(
            $store,
            (string) $validated['type'],
            (array) ($validated['properties'] ?? []),
            $request->session()?->getId(),
            null,
            $validated['client_event_id'] ?? null,
        );

        return response()->json([
            'accepted' => true,
            'event_id' => $event?->getKey(),
        ], 202);
    }
}
