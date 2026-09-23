<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsExport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsExportController extends Controller
{
    public function download(int $analyticsExport): StreamedResponse
    {
        Gate::authorize('view-analytics');
        $storeId = (int) app('current_store')->getKey();
        $export = AnalyticsExport::query()
            ->where('store_id', $storeId)
            ->whereKey($analyticsExport)
            ->where('status', 'completed')
            ->whereNotNull('storage_key')
            ->firstOrFail();

        abort_unless(Storage::disk('local')->exists($export->storage_key), 404);

        return Storage::disk('local')->download(
            $export->storage_key,
            'analytics-'.$export->from_date->format('Y-m-d').'-'.$export->to_date->format('Y-m-d').'.csv',
            ['Content-Type' => 'text/csv'],
        );
    }
}
