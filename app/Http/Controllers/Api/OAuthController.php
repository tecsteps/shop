<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OAuthController extends Controller
{
    public function authorize(): JsonResponse
    {
        return $this->notImplemented();
    }

    public function token(): JsonResponse
    {
        return $this->notImplemented();
    }

    private function notImplemented(): JsonResponse
    {
        return response()->json([
            'message' => 'OAuth app ecosystem endpoints are deferred for initial implementation.',
        ], 501);
    }
}
