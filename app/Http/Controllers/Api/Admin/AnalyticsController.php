<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateOrderExport;
use App\Models\Order;
use App\Models\OrderExport;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function summary(Request $request, int $storeId): JsonResponse
    {
        $this->authorize('viewAnalytics', app('current_store'));
        $data = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from'], 'granularity' => ['sometimes', 'in:day,week,month']]);
        $daily = $this->analytics->getDailyMetrics(app('current_store'), $data['from'], $data['to']);

        return response()->json(['data' => [
            'revenue_amount' => (int) $daily->sum('revenue_amount'),
            'orders_count' => (int) $daily->sum('orders_count'),
            'visits_count' => (int) $daily->sum('visits_count'),
            'series' => $daily,
        ]]);
    }

    public function exportOrders(Request $request, int $storeId): JsonResponse
    {
        $this->authorize('viewAny', Order::class);
        $data = $request->validate([
            'format' => ['sometimes', 'in:csv'],
            'filters' => ['sometimes', 'array'],
            'filters.status' => ['sometimes', 'in:pending,paid,fulfilled,cancelled,refunded'],
            'filters.financial_status' => ['sometimes', 'string'],
            'filters.created_after' => ['sometimes', 'date'],
            'filters.created_before' => ['sometimes', 'date', 'after_or_equal:filters.created_after'],
        ]);
        $export = OrderExport::withoutGlobalScopes()->create([
            'store_id' => $storeId,
            'user_id' => $request->user()?->id,
            'format' => $data['format'] ?? 'csv',
            'filters_json' => $data['filters'] ?? [],
            'status' => 'queued',
        ]);
        GenerateOrderExport::dispatch($export)->afterCommit();

        return response()->json([
            'export_id' => $export->id,
            'status' => 'queued',
            'created_at' => $export->created_at?->toIso8601String(),
        ], 202);
    }

    public function export(Request $request, int $storeId, int $exportId): JsonResponse|StreamedResponse
    {
        $this->authorize('viewAny', Order::class);
        $export = OrderExport::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($exportId);
        if ($request->boolean('download')) {
            abort_unless($export->status === 'completed' && $export->storage_key !== null, 409, 'The export is not ready.');

            return Storage::disk('local')->download($export->storage_key, "orders-{$export->id}.csv", ['Content-Type' => 'text/csv']);
        }

        $expires = now()->addHour();

        return response()->json(['data' => [
            'id' => $export->id,
            'status' => $export->status,
            'format' => $export->format,
            'row_count' => $export->row_count,
            'download_url' => $export->status === 'completed'
                ? URL::temporarySignedRoute('api.admin.exports.show', $expires, ['storeId' => $storeId, 'exportId' => $export->id, 'download' => 1])
                : null,
            'download_expires_at' => $export->status === 'completed' ? $expires->toIso8601String() : null,
            'created_at' => $export->created_at?->toIso8601String(),
            'completed_at' => $export->completed_at?->toIso8601String(),
        ]]);
    }
}
