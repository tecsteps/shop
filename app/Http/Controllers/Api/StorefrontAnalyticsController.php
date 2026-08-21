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
            'events' => ['sometimes', 'array', 'min:1', 'max:100'],
            'events.*.type' => ['required_with:events', 'string'],
            'events.*.properties' => ['nullable', 'array'],
            'events.*.session_id' => ['nullable', 'string', 'max:255'],
            'events.*.client_event_id' => ['nullable', 'string', 'max:255'],
            'events.*.occurred_at' => ['nullable', 'date'],
            'type' => ['required_without:events', 'string'],
            'properties' => ['nullable', 'array'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'client_event_id' => ['nullable', 'string', 'max:255'],
            'occurred_at' => ['nullable', 'date'],
        ]);
        $events = $data['events'] ?? [$data];
        $stored = [];
        foreach ($events as $event) {
            $stored[] = $this->analytics->track(app('current_store'), $event['type'], $event['properties'] ?? [], $event['session_id'] ?? ($request->hasSession() ? $request->session()->getId() : null), $request->user('customer')?->getKey(), $event['client_event_id'] ?? null, isset($event['occurred_at']) ? new \DateTimeImmutable($event['occurred_at']) : null);
        }

        return response()->json(['ids' => collect($stored)->map->getKey()->all(), 'accepted' => count($stored)], 202);
    }
}
