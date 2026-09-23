<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateOrderExport;
use App\Models\Order;
use App\Models\OrderExport;
use App\Models\Store;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderExportController extends Controller
{
    public function store(Request $request, int $storeId): JsonResponse
    {
        $store = $this->currentStore($storeId);
        Gate::authorize('viewAny', Order::class);
        $payload = json_decode($request->getContent());

        if (! is_object($payload) && $payload !== []) {
            throw ValidationException::withMessages(['request' => 'The request body must be a JSON object.']);
        }

        if (is_object($payload) && property_exists($payload, 'filters') && ! is_object($payload->filters)) {
            throw ValidationException::withMessages(['filters' => 'The filters field must be an object.']);
        }

        $validated = $request->validate([
            'format' => ['sometimes', Rule::in(['csv'])],
            'filters' => ['sometimes', 'array'],
            'filters.status' => ['sometimes', Rule::in(['pending', 'paid', 'fulfilled', 'cancelled', 'refunded'])],
            'filters.financial_status' => ['sometimes', Rule::in(['pending', 'paid', 'partially_refunded', 'refunded'])],
            'filters.fulfillment_status' => ['sometimes', Rule::in(['unfulfilled', 'partial', 'fulfilled'])],
            'filters.customer_id' => [
                'sometimes', 'integer', 'min:1',
                Rule::exists('customers', 'id')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('store_id', $store->getKey())),
            ],
            'filters.created_after' => ['sometimes', 'date'],
            'filters.created_before' => ['sometimes', 'date', 'after_or_equal:filters.created_after'],
            'filters.query' => ['sometimes', 'string', 'max:255'],
        ]);

        $export = OrderExport::query()->create([
            'store_id' => $store->getKey(),
            'requested_by_user_id' => $request->user()->getKey(),
            'format' => $validated['format'] ?? 'csv',
            'filters_json' => $validated['filters'] ?? [],
            'status' => 'queued',
        ]);

        GenerateOrderExport::dispatch((int) $export->getKey())->onConnection('database');

        return response()->json([
            'export_id' => $export->getKey(),
            'status' => $export->status,
            'created_at' => $export->created_at?->toISOString(),
        ], 202);
    }

    public function show(int $storeId, int $exportId): JsonResponse
    {
        $store = $this->currentStore($storeId);
        Gate::authorize('viewAny', Order::class);
        $export = OrderExport::query()
            ->where('store_id', $store->getKey())
            ->findOrFail($exportId);
        $downloadExpiresAt = $export->status === 'completed' && filled($export->storage_key)
            ? now()->addHour()
            : null;
        $downloadUrl = $downloadExpiresAt === null
            ? null
            : URL::temporarySignedRoute('api.admin.exports.download', $downloadExpiresAt, [
                'storeId' => $store->getKey(),
                'exportId' => $export->getKey(),
            ]);

        return response()->json(['data' => [
            'id' => $export->getKey(),
            'status' => $export->status,
            'format' => $export->format,
            'row_count' => $export->row_count,
            'download_url' => $downloadUrl,
            'download_expires_at' => $downloadExpiresAt?->toISOString(),
            'created_at' => $export->created_at?->toISOString(),
            'completed_at' => $export->completed_at?->toISOString(),
        ]]);
    }

    public function download(int $storeId, int $exportId): StreamedResponse
    {
        $export = OrderExport::query()
            ->where('store_id', $storeId)
            ->where('status', 'completed')
            ->findOrFail($exportId);

        abort_unless(is_string($export->storage_key) && Storage::disk('local')->exists($export->storage_key), 404);

        return Storage::disk('local')->download(
            $export->storage_key,
            "orders-export-{$export->getKey()}.csv",
            ['Content-Type' => 'text/csv; charset=utf-8'],
        );
    }

    private function currentStore(int $storeId): Store
    {
        $store = app('current_store');
        abort_unless($store instanceof Store && (int) $store->getKey() === $storeId, 404);

        return $store;
    }
}
