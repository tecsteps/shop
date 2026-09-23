<?php

namespace App\Jobs;

use App\Models\AnalyticsExport;
use App\Models\Store;
use App\Services\AnalyticsReportingService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class GenerateAnalyticsExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $exportId) {}

    public function handle(AnalyticsReportingService $reports): void
    {
        $export = AnalyticsExport::query()->findOrFail($this->exportId);
        $export->update(['status' => 'processing', 'error_message' => null]);
        $store = Store::query()->findOrFail($export->store_id);
        $report = $reports->report(
            $store,
            CarbonImmutable::parse($export->from_date, 'UTC'),
            CarbonImmutable::parse($export->to_date, 'UTC'),
            $export->channel,
            $export->device,
        );
        $stream = fopen('php://temp', 'w+');

        if ($stream === false) {
            throw new RuntimeException('Could not open a temporary stream for the analytics export.');
        }

        try {
            fputcsv($stream, ['Date', 'Sales amount', 'Currency', 'Orders'], ',', '"', '');

            foreach ($report['daily'] as $day) {
                fputcsv($stream, [
                    $day['date'],
                    $day['revenue_amount'],
                    $report['currency'],
                    $day['orders_count'],
                ], ',', '"', '');
            }

            rewind($stream);
            $contents = stream_get_contents($stream);

            if ($contents === false) {
                throw new RuntimeException('The analytics export could not be read from its temporary stream.');
            }

            $path = 'analytics-exports/'.$export->store_id.'/'.$export->getKey().'.csv';

            if (! Storage::disk('local')->put($path, $contents)) {
                throw new RuntimeException('The analytics export could not be written to storage.');
            }

            $export->update([
                'status' => 'completed',
                'storage_key' => $path,
                'completed_at' => now(),
                'error_message' => null,
            ]);
        } finally {
            fclose($stream);
        }
    }

    public function failed(?Throwable $exception): void
    {
        AnalyticsExport::query()->whereKey($this->exportId)->update([
            'status' => 'failed',
            'error_message' => $exception === null ? null : mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }
}
