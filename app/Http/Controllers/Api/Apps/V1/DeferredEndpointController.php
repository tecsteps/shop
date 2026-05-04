<?php

namespace App\Http\Controllers\Api\Apps\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DeferredEndpointController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'message' => 'App API endpoints are deferred for initial implementation.',
        ], 501);
    }
}
