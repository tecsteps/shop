<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\DataExportResource;
use App\Models\DataExport;
use App\Models\Store;
use App\Services\OrderExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderExportController extends Controller
{
    public function store(Request $request, Store $store, OrderExportService $exports): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $validated = $request->validate([
            'format' => ['nullable', Rule::in(['csv'])],
            'filters' => ['nullable', 'array'],
            'filters.status' => ['nullable', Rule::in(['pending', 'paid', 'fulfilled', 'cancelled', 'refunded'])],
            'filters.financial_status' => ['nullable', Rule::in(['pending', 'authorized', 'paid', 'partially_refunded', 'refunded', 'voided'])],
            'filters.fulfillment_status' => ['nullable', Rule::in(['unfulfilled', 'partial', 'fulfilled'])],
            'filters.query' => ['nullable', 'string', 'max:255'],
            'filters.created_after' => ['nullable', 'date'],
            'filters.created_before' => ['nullable', 'date', 'after_or_equal:filters.created_after'],
        ]);

        $export = $exports->create($store, $validated['filters'] ?? []);

        return response()->json([
            'export_id' => $export->getKey(),
            'status' => $export->status?->value,
            'created_at' => $export->created_at?->toIso8601String(),
        ], 202);
    }

    public function show(Request $request, Store $store, DataExport $dataExport): DataExportResource
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessExportBelongsToStore($dataExport, $store);

        return DataExportResource::make($dataExport);
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('admin_api_oauth_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function abortUnlessExportBelongsToStore(DataExport $export, Store $store): void
    {
        abort_unless((int) $export->store_id === $store->getKey(), 404);
    }
}
