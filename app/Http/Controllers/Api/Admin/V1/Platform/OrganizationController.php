<?php

namespace App\Http\Controllers\Api\Admin\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\CreateOrganizationRequest;
use App\Http\Resources\Api\Admin\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    public function store(CreateOrganizationRequest $request): JsonResponse
    {
        $organization = Organization::query()->create($request->validated());

        return (new OrganizationResource($organization))->response()->setStatusCode(201);
    }
}
