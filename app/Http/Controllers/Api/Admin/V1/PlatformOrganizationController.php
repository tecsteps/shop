<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\V1\StorePlatformOrganizationRequest;
use App\Http\Resources\Admin\V1\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;

class PlatformOrganizationController extends Controller
{
    public function store(StorePlatformOrganizationRequest $request): JsonResponse
    {
        $organization = Organization::query()->create($request->validated());

        return OrganizationResource::make($organization)
            ->response()
            ->setStatusCode(201);
    }
}
