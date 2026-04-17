<?php

namespace App\Http\Middleware;

use App\Services\AnalyticsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    public function __construct(protected AnalyticsService $analyticsService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && ! $request->ajax() && app()->bound('current_store')) {
            $store = app('current_store');
            $customer = auth('customer')->user();

            $this->analyticsService->track(
                $store,
                'page_view',
                ['url' => $request->path()],
                $request->session()->getId(),
                $customer?->id,
            );
        }

        return $response;
    }
}
