<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * OAuth and app ecosystem stub per spec 02 section 5.
 * Returns 501 Not Implemented for all endpoints (deferred per roadmap).
 */
class OauthStubController extends Controller
{
    public function authorize(): JsonResponse
    {
        return $this->notImplemented();
    }

    public function token(): JsonResponse
    {
        return $this->notImplemented();
    }

    public function appResource(): JsonResponse
    {
        return $this->notImplemented();
    }

    protected function notImplemented(): JsonResponse
    {
        return response()->json([
            'message' => 'OAuth and app ecosystem endpoints are not implemented yet.',
        ], 501);
    }
}
