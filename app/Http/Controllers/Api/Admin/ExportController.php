<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderExportRequest;
use App\Http\Resources\Admin\ExportResource;
use App\Jobs\GenerateOrderExport;
use App\Models\ApiToken;
use App\Models\Export;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

class ExportController extends Controller
{
    public function storeOrders(StoreOrderExportRequest $request, Store $store): JsonResponse
    {
        $validated = $request->validated();
        $export = Export::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'user_id' => $this->currentApiToken()?->user_id,
            'type' => Export::TypeOrders,
            'format' => $validated['format'] ?? Export::FormatCsv,
            'status' => Export::StatusQueued,
            'filters_json' => $validated['filters'] ?? [],
        ]);

        GenerateOrderExport::dispatch($export->id);

        return response()->json([
            'export_id' => $export->id,
            'status' => Export::StatusQueued,
            'created_at' => $export->created_at?->toISOString(),
        ], 202);
    }

    public function show(Store $store, int $export): ExportResource
    {
        return new ExportResource(
            Export::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->whereKey($export)
                ->firstOrFail()
        );
    }

    private function currentApiToken(): ?ApiToken
    {
        return app()->bound('current_api_token')
            ? app('current_api_token')
            : null;
    }
}
