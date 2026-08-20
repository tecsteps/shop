<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontAnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'properties' => ['nullable', 'array'],
            'client_event_id' => ['nullable', 'string', 'max:255'],
        ]);
        $event = $this->analytics->track(app('current_store'), $data['type'], $data['properties'] ?? [], $request->hasSession() ? $request->session()->getId() : null, $request->user('customer')?->getKey(), $data['client_event_id'] ?? null);

        return response()->json(['id' => $event->getKey(), 'status' => 'accepted'], 202);
    }
}
