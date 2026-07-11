<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analyticsService) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'events' => ['required', 'array', 'min:1', 'max:50'],
            'events.*.type' => ['required', 'string'],
            'events.*.properties' => ['sometimes', 'array'],
            'events.*.client_event_id' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
        /** @var Store $store */
        $store = app('current_store');

        foreach ($validated['events'] as $event) {
            $this->analyticsService->track($store, $event['type'], $event['properties'] ?? [], $request->hasSession() ? $request->session()->getId() : null, null, Arr::get($event, 'client_event_id'));
        }

        return response()->json(['accepted' => count($validated['events'])], Response::HTTP_ACCEPTED);
    }
}
